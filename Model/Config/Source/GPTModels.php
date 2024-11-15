<?php

namespace MageOS\AutomaticTranslation\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use OpenAI;
use OpenAI\Client as OpenAIClient;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use Exception;

class GPTModels implements OptionSourceInterface
{
    /**
     * @var OpenAIClient|null
     */
    protected ?OpenAIClient $openAIclient = null;
    /**
     * @var OpenAI
     */
    protected OpenAI $openAI;
    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;

    /**
     * @param OpenAI $openAI
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        OpenAI $openAI,
        ModuleConfig $moduleConfig
    ) {
        $this->openAI = $openAI;
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * @return void
     */
    protected function initClient()
    {
        $apiKey = $this->moduleConfig->getOpenAIApiKey();
        $organization = $this->moduleConfig->getOpenAIOrgID();

        $this->openAIclient = $this->openAI::client($apiKey, $organization);
    }

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        $optionArray = [['value' => '', 'label' => __('-- Please Select --')]];

        if (!empty($this->moduleConfig->getOpenAIOrgID()) && !empty($this->moduleConfig->getOpenAIApiKey())) {
            try {
                if (empty($this->openAIclient)) {
                    $this->initClient();
                }

                $models = $this->openAIclient->models()->list()->toArray();

                foreach ($models['data'] as $model) {
                    $optionArray[] = [
                        'value' => $model['id'],
                        'label' => $model['root']
                    ];
                }
            } catch (Exception $e) {
                return $optionArray;
            }

        }

        return $optionArray;
    }
}