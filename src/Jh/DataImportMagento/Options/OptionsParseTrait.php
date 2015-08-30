<?php

namespace Jh\DataImportMagento\Options;

/**
 * Class OptionsParseTrait
 * @package Jh\DataImportMagento\Options
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
trait OptionsParseTrait
{
    /**
     * @param $allowedOptions
     * @param $options
     * @return array
     */
    public function parseOptions(array $allowedOptions, array $options)
    {
        $cleanOptions = [];
        foreach ($options as $optionName => $optionValue) {
            if (isset($allowedOptions[$optionName])) {
                $cleanOptions[$optionName] = $optionValue;
            }
        }
        $cleanOptions       = array_merge($allowedOptions, $cleanOptions);
        $notAcceptedOptions = array_keys(array_diff_key($options, $cleanOptions));

        if (count($notAcceptedOptions)) {
            throw new \InvalidArgumentException(
                sprintf("'%s' are not accepted options'", implode("', '", $notAcceptedOptions))
            );
        }

        return $cleanOptions;
    }
}
