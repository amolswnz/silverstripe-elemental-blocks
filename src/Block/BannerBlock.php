<?php
namespace SilverStripe\ElementalBlocks\Block;

use Meridian\Lux\Helpers\MeridianConfig;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\TinyMCEConfig;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextField;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;

class BannerBlock extends FileBlock
{
    private static $icon = 'font-icon-block-banner';

    private static $db = [
        'Content' => 'HTMLText',
        'CallToActionLink' => 'Link',
        'BannerHeight' => 'Int',
        'ForegroundColour' => 'Varchar(25)'
    ];

    private static $defaults = [
        "BannerHeight" => 300,
    ];

    private static $singular_name = 'banner';

    private static $plural_name = 'banners';

    private static $table_name = 'S_EB_BannerBlock';

    public function getType()
    {
        return _t(__CLASS__ . '.BlockType', 'Banner');
    }

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            // Remove default scaffolded relationship fields
            $fields->removeByName('CallToActionLinkID');

            // Move the file upload field to be before the content
            $upload = $fields->fieldByName('Root.Main.File');
            $fields->insertBefore('Content', $upload);
            $heightField = NumericField::create('BannerHeight', 'Banner Height')
                ->setDescription('Specify height of the banner value in terms of number of pixels (For example: 250)')
                ->setAttribute('value', $this->BannerHeight ? $this->BannerHeight : 300);
            $fields->addFieldToTab(
                'Root.Main',
                $heightField,
                'Content'
            );

            $colours = MeridianConfig::config()->get('text_colours');
            $fields->addFieldsToTab(
                'Root.Main',
                DropdownField::create('ForegroundColour', 'Foreground text colour', $colours),
                'CallToActionLink'
            );
            // Set the height of the content fields
            $fields->fieldByName('Root.Main.Content')->setRows(5);
        });

        // Ensure TinyMCE's javascript is loaded before the blocks overrides
        Requirements::javascript(TinyMCEConfig::get()->getScriptURL());
        Requirements::javascript('silverstripe/elemental-blocks:client/dist/js/bundle.js');
        Requirements::css('silverstripe/elemental-blocks:client/dist/styles/bundle.css');

        return parent::getCMSFields();
    }

    /**
     * For the frontend, return a parsed set of data for use in templates
     *
     * @return ArrayData|null
     */
    public function CallToActionLink()
    {
        return $this->decodeLinkData($this->getField('CallToActionLink'));
    }

    /**
     * Add the banner content instead of the image title
     *
     * {@inheritDoc}
     */
    public function getSummary()
    {
        if ($this->File() && $this->File()->exists()) {
            return $this->getSummaryThumbnail() . $this->dbObject('Content')->Summary(20);
        }
        return '';
    }

    /**
     * Given a set of JSON data, decode it, attach the relevant Page object and return as ArrayData
     *
     * @param string $linkJson
     * @return ArrayData|null
     */
    protected function decodeLinkData($linkJson)
    {
        if (!$linkJson || $linkJson === 'null') {
            return;
        }

        $data = ArrayData::create(Convert::json2obj($linkJson));

        // Link page, if selected
        if ($data->PageID) {
            $data->setField('Page', self::get_by_id(SiteTree::class, $data->PageID));
        }

        return $data;
    }
}
