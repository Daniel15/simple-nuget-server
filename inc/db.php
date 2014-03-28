<?php
class DB {
	private static $conn;

	public static function init() {
		static::$conn = new PDO(Config::$dbName);
		static::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		static::createTables();
	}

	private static function createTables() {
		static::$conn->exec('
			CREATE TABLE IF NOT EXISTS packages (
				PackageId TEXT PRIMARY KEY,
				Title TEXT,
				DownloadCount INTEGER NOT NULL DEFAULT 0,
				LatestVersion TEXT
			);
			CREATE INDEX IF NOT EXISTS packages_DownloadCount ON packages (DownloadCount);
			CREATE INDEX IF NOT EXISTS packages_Title ON packages (Title);

			CREATE TABLE IF NOT EXISTS versions (
				VersionId INTEGER PRIMARY KEY,
				PackageId TEXT,
				Title TEXT,
				Description TEXT,
				Created INTEGER,
				Version TEXT,
				PackageHash TEXT,
				PackageHashAlgorithm TEXT,
				Dependencies TEXT,
				PackageSize INTEGER,
				ReleaseNotes TEXT,
				VersionDownloadCount INTEGER NOT NULL DEFAULT 0,
				Tags TEXT,
				LicenseUrl TEXT,
				ProjectUrl TEXT,
				IconUrl TEXT,
				Authors TEXT,
				Owners TEXT,
				RequireLicenseAcceptance BOOLEAN,
				Copyright TEXT,
				IsPrerelease BOOLEAN
			);
			CREATE INDEX IF NOT EXISTS versions_Version ON versions (Version);
		');
	}

	public static function countPackages() {
		return static::$conn->query('
			SELECT COUNT(PackageId) AS count FROM packages
		')->fetchColumn();
	}

	public static function searchPackages($params) {
		$query_params = [];
		$where = '1=1';

		// Defaults
		if (empty($params['orderBy'])) {
			$params['orderBy'] = 'DownloadCount desc, Id';
		}

		if (!$params['includePrerelease']) {
			$where .= ' AND IsPrerelease = 0';
		}
		if (!empty($params['searchQuery'])) {
			$where .= ' AND
				(packages.Title LIKE :searchQuery
				OR packages.PackageId LIKE :searchQuery)';
			$query_params['searchQuery'] = '%' . $params['searchQuery'] . '%';
		}

		switch ($params['filter']) {
			case '':
				break;

			case 'IsAbsoluteLatestVersion':
			case 'IsLatestVersion':
				$where .= ' AND versions.Version = packages.LatestVersion';
				break;

			default:
				throw new Exception('Unknown filter "' . $params['filter'] . '"');
		}

		$order = static::parseOrderBy($params['orderBy']);

		return static::doSearch($where, $order, $query_params);
	}

	public static function findByID($id) {
		return static::doSearch(
			'packages.PackageId = :id',
			'versions.Version DESC',
			[
				'id' => $id
			]
		);
	}

	private static function parseOrderBy($order_by) {
		$valid_sort_columns = [
			'downloadcount' => 'packages.DownloadCount',
			'id' => 'packages.PackageId',
			'published' => 'versions.Created',
		];
		$columns = explode(',', $order_by);
		$output = [];

		foreach ($columns as $column) {
			$direction = 'asc';
			$column = trim(strtolower($column));
			if (strpos($column, ' ') !== false) {
				$pieces = explode(' ', $column);
				$column = $pieces[0];
				$direction = $pieces[1];
			}
			if (!isset($valid_sort_columns[$column])) {
				throw new Exception('Unknown sort column "' . $column . '"');
			}
			if ($direction !== 'asc' && $direction !== 'desc') {
				throw new Exception('Unknown sort order "' . $direction .'"');
			}
			$output[] = $valid_sort_columns[$column] . ' ' . $direction;
		}
		return implode(', ', $output);
	}

	// *Assumes* $where and $order are sanitised!! This should be done at the
	// callsites!
	private static function doSearch($where, $order, $params) {
		// TODO: Move this to a view
		$stmt = static::$conn->prepare('
			SELECT
				packages.PackageId, packages.DownloadCount,
				packages.LatestVersion,
				versions.Title, versions.Description,
				versions.Tags, versions.LicenseUrl, versions.ProjectUrl,
				versions.IconUrl, versions.Authors, versions.Owners,
				versions.RequireLicenseAcceptance, versions.Copyright,
				versions.Created, versions.Version, versions.PackageHash,
				versions.PackageHashAlgorithm, versions.PackageSize,
				versions.Dependencies, versions.ReleaseNotes,
				versions.VersionDownloadCount, versions.IsPrerelease
			FROM packages
			INNER JOIN versions ON packages.PackageId = versions.PackageId
			WHERE ' . $where . '
			ORDER BY ' . $order . '
		');
		$stmt->execute($params);
		return $stmt->fetchAll();
	}

	public static function validateIdAndVersion($id, $version) {
		$stmt = static::$conn->prepare('
			SELECT COUNT(1)
			FROM versions
			WHERE PackageId = :id AND Version = :version
		');
		$stmt->execute([
			':id' => $id,
			':version' => $version,
		]);
		return $stmt->fetchColumn() == 1;
	}

	public static function incrementDownloadCount($id, $version) {
		$stmt = static::$conn->prepare('
			UPDATE versions
			SET VersionDownloadCount = VersionDownloadCount + 1
			WHERE PackageId = :id AND Version = :version
		');
		$stmt->execute([
			':id' => $id,
			':version' => $version,
		]);

		// Denormalised since this isn't much of a perf issue and improves
		// query performance
		$stmt = static::$conn->prepare('
			UPDATE packages
			SET DownloadCount = DownloadCount + 1
			WHERE PackageId = :id
		');
		$stmt->execute([
			':id' => $id,
		]);
	}

	public static function insertOrUpdatePackage($params) {
		// Upserts aren't standardised across DBMSes :(
		// Easiest thing here is to just do an insert followed by an update.
		$stmt = static::$conn->prepare('
			INSERT OR IGNORE INTO packages
				(PackageId, Title, LatestVersion)
			VALUES
				(:id, :title, :version)
		');
		$stmt->execute($params);

		$stmt = static::$conn->prepare('
			UPDATE packages
			SET Title = :title, LatestVersion = :version
			WHERE PackageId = :id'
		);
		$stmt->execute($params);
	}

	public static function insertVersion($params) {
		$params[':Created'] = time();
		$params[':Dependencies'] = json_encode($params[':Dependencies']);
		$params[':IsPrerelease'] = empty($params[':IsPrerelease']) ? '0' : '1';
		$params[':RequireLicenseAcceptance'] =
			empty($params[':RequireLicenseAcceptance']) ? '0' : '1';

		$stmt = static::$conn->prepare('
			INSERT INTO versions (
				Authors, Copyright, Created, Dependencies, Description,
				IconUrl, IsPrerelease, LicenseUrl, Owners, PackageId,
				PackageHash, PackageHashAlgorithm, PackageSize, ProjectUrl,
				ReleaseNotes, RequireLicenseAcceptance, Tags, Title, Version
			)
			VALUES (
				:Authors, :Copyright, :Created, :Dependencies, :Description,
				:IconUrl, :IsPrerelease, :LicenseUrl, :Owners, :PackageId,
				:PackageHash, :PackageHashAlgorithm, :PackageSize, :ProjectUrl,
				:ReleaseNotes, :RequireLicenseAcceptance, :Tags, :Title, :Version
			)
		');
		$stmt->execute($params);
	}
}

DB::init();
