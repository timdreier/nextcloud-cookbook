<?php

namespace OCA\Cookbook\Helper\Filter\JSON;

use OCA\Cookbook\Exception\InvalidRecipeException;
use OCA\Cookbook\Helper\Filter\AbstractJSONFilter;
use OCA\Cookbook\Helper\TextCleanupHelper;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

/**
 * Fix the tools list.
 *
 * The tools entry is mandatory for the recipes and an array.
 * This filter ensures, an entry is present.
 *
 * If no entry is found, an empty array is added.
 * If there is already an array present, the entries are cleaned up to prevent malicious chars to be present.
 */
class FixToolsFilter extends AbstractJSONFilter {
	private const TOOLS = 'tool';

	/** @var IL10N */
	private $l;

	/** @var LoggerInterface */
	private $logger;

	/** @var TextCleanupHelper */
	private $textCleaner;

	public function __construct(IL10N $l, LoggerInterface $logger, TextCleanupHelper $textCleanupHelper) {
		$this->l = $l;
		$this->logger = $logger;
		$this->textCleaner = $textCleanupHelper;
	}

	public function apply(array &$json): bool {
		if (!isset($json[self::TOOLS])) {
			$json[self::TOOLS] = [];
			return true;
		}

		if (!is_array($json[self::TOOLS]) && !is_string($json[self::TOOLS])) {
			throw new InvalidRecipeException($this->l->t('Could not parse recipe tools. Expected array or string.'));
		}

		$tools = array();

		if (!is_array($json[self::TOOLS])) {
			$t = trim($json[self::TOOLS]);
			$tools[] = $this->textCleaner->cleanUp($t, false);
		}
		else{
			$tools = array_map(function ($t) {
				$t = trim($t);
				$t = $this->textCleaner->cleanUp($t, false);
				return $t;
			}, $json[self::TOOLS]);
			$tools = array_filter($tools, fn($t) => ($t));
			ksort($tools);
			$tools = array_values($tools);
		}

		$changed = $tools !== $json[self::TOOLS];
		$json[self::TOOLS] = $tools;
		return $changed;
	}
}
