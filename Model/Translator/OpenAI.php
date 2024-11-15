<?php

namespace MageOS\AutomaticTranslation\Model\Translator;

use MageOS\AutomaticTranslation\Api\TranslatorInterface;
use OpenAI as OpenAITranslator;
use OpenAI\Client as OpenAIClient;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use Exception;

class OpenAI implements TranslatorInterface
{
    /**
     * @var OpenAIClient|null
     */
    protected ?OpenAIClient $translator = null;
    /**
     * @var OpenAITranslator
     */
    protected OpenAITranslator $openAITranslator;
    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;

    /**
     * @param OpenAITranslator $openAITranslator
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        OpenAITranslator $openAITranslator,
        ModuleConfig $moduleConfig
    ) {
        $this->openAITranslator = $openAITranslator;
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * @return void
     */
    public function initTranslator()
    {
        $apiKey = $this->moduleConfig->getOpenAIApiKey();
        $organization = $this->moduleConfig->getOpenAIOrgID();

        $this->translator = $this->openAITranslator::client($apiKey, $organization);
    }

    /**
     * @param string $text
     * @param string $targetLang
     * @param string|null $sourceLang
     * @return string
     * @throw Exception
     */
    public function translate(string $text, string $targetLang, ?string $sourceLang = null): string
    {
        if (empty($this->translator)) {
            $this->initTranslator();
        }

        $prompt = 'Translate this text';
        $prompt .= (!empty($sourceLang)) ? ' from ' . $sourceLang : '';
        $prompt .= ' to ' . $targetLang;
        $prompt .= ': ' . $text;

        try {
            $result = $this->translator->completions()->create([
                'model' => $this->moduleConfig->getOpenAIModel(),
                'prompt' => $prompt
            ]);

            return trim($result['choices'][0]['text']);
        } catch (Exception $e) {
            $result = $this->translator->chat()->create([
                'model' => $this->moduleConfig->getOpenAIModel(),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ])->toArray();

            return trim($result['choices'][0]['message']['content']);
        }
    }
}