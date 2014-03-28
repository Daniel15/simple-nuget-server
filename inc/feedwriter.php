<?php
class FeedWriter {
	private $feedID;
	private $baseURL;

    public function __construct($id) {
    	$this->feedID = $id;
        $this->baseURL =
            'http://' .
            $_SERVER['HTTP_HOST'] .
            dirname($_SERVER['REQUEST_URI']) . '/';
    }

    public function write(array $results) {
        $this->beginFeed();
        foreach ($results as $result) {
            $this->addEntry($result);
        }
        return $this->feed->asXML();
    }

    public function writeToOutput(array $results) {
		header('Content-Type: application/atom+xml; type=feed; charset=UTF-8');
		echo $this->write($results);
    }

    private function beginFeed() {
        $this->feed = simplexml_load_string(
            '<?xml version="1.0" encoding="utf-8" ?>
            <feed
                xml:base="https://www.nuget.org/api/v2/"
                xmlns="http://www.w3.org/2005/Atom"
                xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices"
                xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata"
            >
            </feed>
        ');

        $this->feed->addChild('id', $this->baseURL . $this->feedID);
        $this->addWithAttribs($this->feed, 'title', $this->feedID, ['type' => 'text']);
        $this->feed->addChild('updated', static::formatDate(time()));
        $this->addWithAttribs($this->feed, 'link', null, [
            'rel' => 'self',
        	'title' => $this->feedID,
        	'href' => $this->feedID
        ]);
    }

    private function addEntry($row) {
        $entry_id = 'Packages(Id=\'' . $row['PackageId'] . '\',Version=\'' . $row['Version'] . '\')';
        $entry = $this->feed->addChild('entry');
        $entry->addChild('id', 'https://www.nuget.org/api/v2/' . $entry_id);
        $this->addWithAttribs($entry, 'category', null, [
            'term' => 'NuGetGallery.V2FeedPackage',
            'scheme' => 'http://schemas.microsoft.com/ado/2007/08/dataservices/scheme',
        ]);
        $this->addWithAttribs($entry, 'link', null, [
            'rel' => 'edit',
            'title' => 'V2FeedPackage',
            'href' => $entry_id,
        ]);
        // Yes, this is correct. This "title" is actually the package ID
        // The actual title is in the metadata section. lolwat.
        $this->addWithAttribs($entry, 'title', $row['PackageId'], ['type' => 'text']);
        $this->addWithAttribs($entry, 'summary', null, ['type' => 'text']);
        $entry->addChild('updated', static::formatDate($row['Created']));

        $authors = $entry->addChild('author');
        $authors->addChild('name', $row['Authors']);

        $this->addWithAttribs($entry, 'link', null, [
            'rel' => 'edit-media',
            'title' => 'V2FeedPackage',
            'href' => $entry_id . '/$value',
        ]);
        $this->addWithAttribs($entry, 'content', null, [
            'type' => 'application/zip',
            'src' => $this->baseURL . 'download/' . $row['PackageId'] . '/' . $row['Version'],
        ]);
        $this->addEntryMeta($entry, $row);
    }

    private function addEntryMeta($entry, $row) {
        $properties = $entry->addChild(
            'properties',
            null,
            'http://schemas.microsoft.com/ado/2007/08/dataservices/metadata'
        );

        $meta = [
            'Version' => $row['Version'],
            'NormalizedVersion' => $row['Version'],
            'Copyright' => $row['Copyright'],
            'Created' => static::renderMetaDate($row['Created']),
            'Dependencies' => $this->renderDependencies($row['Dependencies']),
            'Description' => $row['Description'],
            'DownloadCount' => ['value' => $row['DownloadCount'], 'type' => 'Edm.Int32'],
            'GalleryDetailsUrl' => $this->baseURL . 'details/' . $row['PackageId'] . '/' . $row['Version'],
            'IconUrl' => $row['IconUrl'],
            'IsLatestVersion' => static::renderMetaBoolean($row['LatestVersion'] === $row['Version']),
            'IsAbsoluteLatestVersion' => static::renderMetaBoolean($row['LatestVersion'] === $row['Version']),
            'IsPrerelease' => static::renderMetaBoolean($row['IsPrerelease']),
            'Language' => null,
            'Published' => static::renderMetaDate($row['Created']),
            'PackageHash' => $row['PackageHash'],
            'PackageHashAlgorithm' => $row['PackageHashAlgorithm'],
            'PackageSize' => ['value' => $row['PackageSize'], 'type' => 'Edm.Int64'],
            'ProjectUrl' => $row['ProjectUrl'],
            'ReportAbuseUrl' => '',
            'ReleaseNotes' => $row['ReleaseNotes'],
            'RequireLicenseAcceptance' => static::renderMetaBoolean($row['RequireLicenseAcceptance']),
            'Summary' => null,
            'Tags' => $row['Tags'],
            'Title' => $row['Title'],
            'VersionDownloadCount' => ['value' => $row['VersionDownloadCount'], 'type' => 'Edm.Int32'],
            'MinClientVersion' => '',
            'LastEdited' => ['value' => null, 'type' => 'Edm.DateTime'],
            'LicenseUrl' => $row['LicenseUrl'],
            'LicenseNames' => '',
            'LicenseReportUrl' => '',
        ];

        foreach ($meta as $name => $data) {
            if (is_array($data)) {
                $value = $data['value'];
                $type = $data['type'];
            } else {
                $value = $data;
                $type = null;
            }

            $this->addMeta($properties, $name, $value, $type);
        }

    }

    private static function renderMetaDate($date) {
        return [
            'value' => static::formatDate($date),
            'type' => 'Edm.DateTime'
        ];
    }

    private static function renderMetaBoolean($value) {
    	return [
    		'value' => $value ? 'true' : 'false',
    		'type' => 'Edm.Boolean'
    	];
    }

    private static function formatDate($date) {
        return gmdate('Y-m-d\TH:i:s\Z', $date);
    }

    private function renderDependencies($raw) {
        if (!$raw) {
            return '';
        }

        $data = json_decode($raw);
        if (!$data) {
            return '';
        }

        $output = [];
        foreach ($data as $id => $version) {
            $output[] = $id . ':' . $version . ':';
        }
        return implode('|', $output);
    }

    private function addWithAttribs($entry, $name, $value, $attributes) {
        $node = $entry->addChild($name, $value);
        foreach ($attributes as $attrib_name => $attrib_value) {
            $node->addAttribute($attrib_name, $attrib_value);
        }
    }

    private function addMeta($entry, $name, $value, $type = null) {
        $node = $entry->addChild(
            $name,
            $value,
            'http://schemas.microsoft.com/ado/2007/08/dataservices'
        );
        if ($type) {
            $node->addAttribute(
                'm:type',
                $type,
                'http://schemas.microsoft.com/ado/2007/08/dataservices/metadata'
            );
        }
        if ($value === null) {
            $node->addAttribute(
                'm:null',
                'true',
                'http://schemas.microsoft.com/ado/2007/08/dataservices/metadata'
            );
        }
    }
}
