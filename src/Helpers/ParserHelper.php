<?php

namespace Mkhyman\Saml2\Helpers;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class Parser
 *
 * @package App\Helpers
 */
class ParserHelper
{
    /**
     * Parse the given console command definition into an array.
     *
     * @param  string  $expression
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public static function parse($expression)
    {
        $name = static::name($expression);

        if (preg_match_all('/\{\s*(.*?)\s*\}/', $expression, $matches) && count($matches[1])) {
            return array_merge([$name], static::parameters($matches[1]));
        }

        return [$name, [], []];
    }

    /**
     * Extract the name of the command from the expression.
     *
     * @param  string  $expression
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected static function name($expression)
    {
        if (!preg_match('/[^\s]+/', $expression, $matches)) {
            throw new InvalidArgumentException('Unable to determine command name from signature.');
        }

        return $matches[0];
    }

    /**
     * Extract all of the parameters from the tokens.
     *
     * @param  array  $tokens
     * @return array
     */
    protected static function parameters($tokens)
    {
        $arguments = [];

        $options = [];

        foreach ($tokens as $token) {
            if (preg_match('/-{2,}(.*)/', $token, $matches)) {
                $options[] = static::parseOption($matches[1]);
            } else {
                $arguments[] = static::parseArgument($token);
            }
        }

        return [$arguments, $options];
    }

    /**
     * Parse an argument expression.
     *
     * @param  string  $token
     * @return \Symfony\Component\Console\Input\InputArgument
     */
    protected static function parseArgument($token)
    {
        $extractedDescription =  static::extractDescription($token);
        $token = $extractedDescription[0];
        $description = $extractedDescription[1];

        switch (true) {
            case static::str_ends_with($token, '?*'):
                return new InputArgument(trim($token, '?*'), InputArgument::IS_ARRAY, $description);
            case static::str_ends_with($token, '*'):
                return new InputArgument(trim($token, '*'), InputArgument::IS_ARRAY | InputArgument::REQUIRED, $description);
            case static::str_ends_with($token, '?'):
                return new InputArgument(trim($token, '?'), InputArgument::OPTIONAL, $description);
            case preg_match('/(.+)\=\*(.+)/', $token, $matches):
                return new InputArgument($matches[1], InputArgument::IS_ARRAY, $description, preg_split('/,\s?/', $matches[2]));
            case preg_match('/(.+)\=(.+)/', $token, $matches):
                return new InputArgument($matches[1], InputArgument::OPTIONAL, $description, $matches[2]);
            default:
                return new InputArgument($token, InputArgument::REQUIRED, $description);
        }
    }

    /**
     * Parse an option expression.
     *
     * @param  string  $token
     * @return \Symfony\Component\Console\Input\InputOption
     */
    protected static function parseOption($token)
    {
        $extractedDescription =  static::extractDescription($token);
        $token = $extractedDescription[0];
        $description = $extractedDescription[1];

        $matches = preg_split('/\s*\|\s*/', $token, 2);

        if (isset($matches[1])) {
            $shortcut = $matches[0];
            $token = $matches[1];
        } else {
            $shortcut = null;
        }

        switch (true) {
            case static::str_ends_with($token, '='):
                return new InputOption(trim($token, '='), $shortcut, InputOption::VALUE_OPTIONAL, $description);
            case static::str_ends_with($token, '=*'):
                return new InputOption(trim($token, '=*'), $shortcut, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, $description);
            case preg_match('/(.+)\=\*(.+)/', $token, $matches):
                return new InputOption($matches[1], $shortcut, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, $description, preg_split('/,\s?/', $matches[2]));
            case preg_match('/(.+)\=(.+)/', $token, $matches):
                return new InputOption($matches[1], $shortcut, InputOption::VALUE_OPTIONAL, $description, $matches[2]);
            default:
                return new InputOption($token, $shortcut, InputOption::VALUE_NONE, $description);
        }
    }

    /**
     * Parse the token into its token and description segments.
     *
     * @param  string  $token
     * @return array
     */
    protected static function extractDescription($token)
    {
        $parts = preg_split('/\s+:\s+/', trim($token), 2);

        return count($parts) === 2 ? $parts : [$token, ''];
    }

    /**
     * Find if string ends with a specific substring
     * 
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    protected static function str_ends_with($haystack, $needle)
    {
        $length = strlen($needle);
        if (!$length) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }
}