<?php

namespace MageOS\AutomaticTranslation\Api;

interface AttributeProviderInterface
{
    const SKIP_TRANSLATION = 'skip_translation';
    const SKIP_TRANSLATION_LABEL = 'Skip translation (uncheck to re-translate)';
    const LAST_TRANSLATION = 'last_translation_date';
    const LAST_TRANSLATION_LABEL = 'Last translation date';
}