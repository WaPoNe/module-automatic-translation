<?php

namespace MageOS\AutomaticTranslation\Plugin;

use Magento\Catalog\Controller\Adminhtml\Product\Save;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Helper\Service;
use MageOS\AutomaticTranslation\Model\Translator;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class AdminhtmlProductBeforeSavePlugin
 * @package MageOS\AutomaticTranslation\Plugin
 */
class AdminhtmlProductBeforeSavePlugin
{

    /**
     * @var ModuleConfig
     */
    private ModuleConfig $moduleConfig;

    /**
     * @var Service
     */
    private Service $serviceHelper;

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * AdminhtmlProductBeforeSavePlugin constructor.
     * @param ModuleConfig $moduleConfig
     * @param Service $serviceHelper
     * @param Translator $translator
     * @param ManagerInterface $messageManager
     * @param Logger $logger
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        Service $serviceHelper,
        Translator $translator,
        ManagerInterface $messageManager,
        Logger $logger
    )
    {
        $this->moduleConfig = $moduleConfig;
        $this->serviceHelper = $serviceHelper;
        $this->translator = $translator;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    /**
     * @param Save $subject
     * @return null
     */
    public function beforeExecute(Save $subject)
    {
        try {
            $request = $subject->getRequest();
            $requestPostValue = $request->getPostValue();
            if ($request->getParam('translate') === "true") {
                $storeId = $request->getParam('store', 0);
                $sourceLanguage = $this->moduleConfig->getSourceLanguage();
                $destinationLanguage = $this->moduleConfig->getDestinationLanguage($storeId);
                if ($sourceLanguage !== $destinationLanguage) {
                    $txtAttributesToTranslate = $this->moduleConfig->getProductTxtAttributeToTranslate($storeId);
                    foreach ($txtAttributesToTranslate as $attributeCode) {
                        if (isset($requestPostValue["product"][$attributeCode]) && is_string($requestPostValue["product"][$attributeCode])) {
                            $parsedContent = $this->serviceHelper->parsePageBuilderHtmlBox($requestPostValue["product"][$attributeCode]);
                            if (is_string($parsedContent)) {
                                $requestPostValue["product"][$attributeCode] = $this->translator->translate(
                                    $parsedContent,
                                    $destinationLanguage
                                );
                            } else {
                                $requestPostValue["product"][$attributeCode] = html_entity_decode(htmlspecialchars_decode($requestPostValue["product"][$attributeCode]));
                                foreach ($parsedContent as $parsedString) {
                                    $parsedString["translation"] = $this->translator->translate(
                                        $parsedString["source"],
                                        $destinationLanguage
                                    );
                                    $requestPostValue["product"][$attributeCode] = str_replace($parsedString["source"], $parsedString["translation"], $requestPostValue["product"][$attributeCode]);
                                }
                                $requestPostValue["product"][$attributeCode] = $this->serviceHelper->encodePageBuilderHtmlBox($requestPostValue["product"][$attributeCode]);
                            }
                            if ($attributeCode === 'url_key') {
                                $requestPostValue["product"][$attributeCode] = strtolower(preg_replace('#[^0-9a-z]+#i', '-',$requestPostValue["product"][$attributeCode]));
                            }
                        }
                    }
                    $request->setPostValue($requestPostValue);
                }
            }
        } catch (\Exception $e) {
            $this->logger->debug(__("An error translating product attributes: %s", $e->getMessage()));
            $this->messageManager->addErrorMessage(__("An error occurred translating product attributes. Try again later. %1", $e->getMessage()));
        }
        return null;
    }
}