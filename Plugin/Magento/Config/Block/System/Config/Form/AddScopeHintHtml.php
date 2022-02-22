<?php

namespace Albedo\ScopeHint\Plugin\Magento\Config\Block\System\Config\Form;

class AddScopeHintHtml{

    /**
     * @var \Albedo\ScopeHint\Service\Config
     */
    protected $configService;

    /**
     * AddScopeHintHtml constructor.
     * @param \Albedo\ScopeHint\Service\Config $configService
     */
    public function __construct(
        \Albedo\ScopeHint\Service\Config $configService
    ){
        $this->configService = $configService;
    }

    public function afterRender(\Magento\Config\Block\System\Config\Form\Field $subject, $result, \Magento\Framework\Data\Form\Element\AbstractElement $element){
        $document = new \DOMDocument();
        try {
            $document->loadHTML('<?xml encoding="utf-8" ?>' . $result, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } catch (\Exception $e){
            return $result;
        }

        $path = $element->getOriginalData('path').'/'.$element->getOriginalData('id');
        $html = $this->configService->getHtmlForConfigPath($path);

        // if there is no html rendered then do not render html 
        if (!$html) {
            return $result;
        }

        $newDiv = $document->createElement('div');

        $fragment = $document->createDocumentFragment();
        $fragment->appendXML('<div class="scope_information_trigger">[Scoped values]</div>');
        $fragment->appendXML('<div class="scope_information_content">'.$html.'</div>');

        $newDiv->appendChild($fragment);
        $newDiv->setAttribute('class', 'scopehint');

        $labels = $document->getElementsByTagName('label');
        $label = $labels->item(0);
        if($label!=null) {
            $label->appendChild($newDiv);
        }

        $result = $document->saveHTML();
        return $result;
    }
}