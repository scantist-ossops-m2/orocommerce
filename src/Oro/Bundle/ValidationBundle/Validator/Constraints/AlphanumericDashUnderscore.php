<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\RegexValidator;

/**
 * The constraint can be used to validate that a value contains only latin letters, numbers and symbols "-" or "_"
 */
class AlphanumericDashUnderscore extends Regex implements AliasAwareConstraintInterface
{
    public const ALIAS = 'alphanumeric_dash_underscore';

    public $message = 'This value should contain only latin letters, numbers and symbols "-" or "_".';
    public $pattern = '/^[-_a-zA-Z0-9]*$/';

    /**
     * {@inheritdoc}
     */
    public function __construct(
        $pattern = null,
        string $message = null,
        string $htmlPattern = null,
        bool $match = null,
        callable $normalizer = null,
        array $groups = null,
        $payload = null,
        array $options = []
    ) {
        $pattern = $pattern ?? ['pattern' => $this->pattern];

        parent::__construct(
            $pattern,
            $message,
            $htmlPattern,
            $match,
            $normalizer,
            $groups,
            $payload,
            $options
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return RegexValidator::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
