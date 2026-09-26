<?php declare(strict_types=1);

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Shef\Options\Main\Options;

/**
 * Опции для страницы настроек
 *
 * языковой файл options.php
 *
 * Tab(prefix)->Option(code) ~> код свойства: prefix_code
 */

$response = ShOptionsConfig::getInstance(
	moduleId: 'shef.insync',
	indexDoc: 'README.md'
);
if(!$response->isSuccess())
{
	return $response;
}

/** @var ShOptionsConfig $options */
$options = $response->getData()['OPTIONS'];

$options->addTab(
	(new Options\Tab('DEF'))
		->setName(Loc::getMessage($options->moduleId.'_TAB_DEF_NAME'))
		->setTitle(Loc::getMessage($options->moduleId.'_TAB_DEF_TITLE'))
		->addOption(
			(new Options\Enum('maxdaydonefile'))
				->setTitle(Loc::getMessage($options->moduleId.'_TAB_DEF_maxdaydonefile'))
				->setDescription(Loc::getMessage($options->moduleId.'_TAB_DEF_maxdaydonefile_desc'))
				->setShowRows(1)
				->setList([
					'1' => Loc::getMessage($options->moduleId.'_TAB_DEF_maxdaydonefile_ENUM_1'),
					'2' => Loc::getMessage($options->moduleId.'_TAB_DEF_maxdaydonefile_ENUM_2_4', ['#VALUE#' => 2]),
					'3' => Loc::getMessage($options->moduleId.'_TAB_DEF_maxdaydonefile_ENUM_2_4', ['#VALUE#' => 3]),
					'5' => Loc::getMessage($options->moduleId.'_TAB_DEF_maxdaydonefile_ENUM_XX', ['#VALUE#' => 5]),
					'15' => Loc::getMessage($options->moduleId.'_TAB_DEF_maxdaydonefile_ENUM_XX', ['#VALUE#' => 15]),
					'30' => Loc::getMessage($options->moduleId.'_TAB_DEF_maxdaydonefile_ENUM_XX', ['#VALUE#' => 30]),
				])
				->setDefValue('3')
		)
);

return $options->get();