<?php

namespace Albedo\ScopeHint\Service;

class Config {

    const CONFIG_TABLE_NAME = 'core_config_data';

    /**
     * @var array
     */
    private $scopesCache = [];

    /**
     * @var array
     */
    private $comfigFound = [];

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var null
     */
    protected $rawConfig = null;

    /**
     * Config constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param $path
     * @return string
     */
    public function getHtmlForConfigPath($path){
        $renderElements[] = $this->renderHintElement('default', 0, 'Default', $this->getConfigValueForPath($path, 'default', 0), $path);
        $structure = $this->getStoresStructure();
        foreach($structure as $websiteId => $storeIds){
            $website = $this->getEntityData('website', $websiteId);
            $renderElements[] = $this->renderHintElement('websites', $websiteId, $website['name'], $this->getConfigValueForPath($path, 'websites', $websiteId), $path);
            foreach($storeIds as $storeId=>$storeCode){
                $store = $this->getEntityData('store', $storeId);
                $renderElements[] = $this->renderHintElement('stores', $storeCode, $store['name'], $this->getConfigValueForPath($path, 'stores', $storeId), $path);
            }
        }

        if(!array_key_exists($path, $this->comfigFound )){
            $renderElements[] = sprintf('<div class="default_config_note">%s</div>', __('This config key uses default value from config.xml!'));
        }

        return '<ul>'.implode('', $renderElements).'</ul>';
    }

    /**
     * @return array|null
     */
    private function getRawConfig(){
        if($this->rawConfig===null) {
            $connection = $this->resourceConnection->getConnection();

            $query = $connection->select()->from(
                $this->resourceConnection->getTableName(self::CONFIG_TABLE_NAME)
            );

            $this->rawConfig = $connection->fetchAll($query);
        }

        return $this->rawConfig;
    }

    /**
     * @return array
     */
    private function getStoresStructure(){
        $structure = [];
        $structureFull = [];
        $stores = $this->storeManager->getStores();
        foreach($stores as $store){
            $structure[$store->getWebsiteId()][$store->getId()] = $store->getCode();
        }

        return $structure;
    }

    /**
     * @param $type
     * @param $id
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getEntityData($type, $id){
        if(!isset($this->scopesCache[$type][$id])) {
            switch ($type) {
                case "website":
                    $website = $this->storeManager->getWebsite($id);
                    $this->scopesCache[$type][$id] = ['name' => $website->getName(), 'code'=>$website->getCode()];
                    break;
                case "store":
                    $store = $this->storeManager->getStore($id);
                    $this->scopesCache[$type][$id] = ['name' => $store->getName(), 'code'=>$store->getCode()];
                    break;
            }
        }

        return $this->scopesCache[$type][$id];
    }

    /**
     * @param $path
     * @param $scope
     * @param $scopeId
     * @return bool|mixed
     */
    private function getConfigValueForPath($path, $scope, $scopeId){
        foreach($this->getRawConfig() as $rowConfig){
            if($rowConfig['path']==$path && $rowConfig['scope']==$scope && $rowConfig['scope_id']==$scopeId){
                return $rowConfig['value'];
            }
        }

        return false;
    }

    /**
     * @param $scope
     * @param $scopeId
     * @param $scopeLabel
     * @param $value
     * @param $path
     * @return string
     */
    private function renderHintElement($scope, $scopeId, $scopeLabel, $value, $path){
        if($value===false){
            $label = __(' - not set -');
        } else {
            $label = $value;
            $this->comfigFound[$path] = true;
        }
        $html = sprintf('<li class="%s">', $scope);
        $html .= sprintf('<span class="scope-%s %s">%s (%s): %s</span>', $scope, $value===false ? 'inherits' : 'value_set', $scopeLabel, $scopeId, $label);
        $html .= '</li>';

        return $html;
    }
}