<?php declare(strict_types=1);

defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \Local\Component\Shef\InSync\ShefInSyncImportStatLocalComponent $component */

Extension::load([
	'ui.forms',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.notification',
	'ui.dialogs.messagebox',
	'ui.alerts',
	'main.loader',
	'ajax',
	'shef-insync.ui-anchors'
]);

/**
 * Сообщение в штатной плашке ui.alerts. Текст — BB-код языкового файла,
 * CTextParser сам экранирует HTML. Была _showError(), которой нет ни в одном
 * модуле линейки: страница падала бы с «Call to undefined function».
 */
$showAlert = static function(string $bbCode, string $type = 'ui-alert-warning'): void
{
	$parser = new \CTextParser();
	echo '<div class="ui-alert ui-alert-xs ', $type, '"><span class="ui-alert-message">',
		$parser->convertText($bbCode),
		'</span></div>';
};

Loc::loadMessages(__FILE__);

$confJs = [
	'gridId' => $arParams['GRID_ID'],
	'agentListId' => 'shAgentList',
	'component' => 'shef.insync:import.stat.local',
	'mode' => 'class',
	'signedParameters' => $this->getComponent()->getSignedParameters()
];

// region GRID ////
foreach($arResult['ROWS'] as $index => $data)
{
	$key = 'row_'.$index.'_'.$data['ORIGINATOR_ID'];
	$actions = [];
	
	$confJsAction = Web\Json::encode([
		'originatorId' => $data['ORIGINATOR_ID'],
		'dateInsertTs' => (
			$data['DATE_INSERT'] instanceof \Bitrix\Main\Type\DateTime
			? $data['DATE_INSERT']->getTimestamp()
			: ''
		)
	]);
	$actions[] = array(
		'TEXT' => Loc::getMessage('ACTION_CLEAR'),
		'ONCLICK' => sprintf(
			'BX.UI.Dialogs.MessageBox.confirm("%s", "%s", (messageBox, button, event) => {BX.ShInSync.ImportStat.clearRow(%s); messageBox.close();}, "%s");',
			Loc::getMessage('ACTION_CLEAR_CONFIRM_MESSAGE'),
			Loc::getMessage('ACTION_CLEAR_CONFIRM_TITLE'),
			$confJsAction,
			Loc::getMessage('ACTION_CLEAR_CONFIRM_BTN'),
		),
	);
	
	// Значения ячеек грид выводит как HTML.
	$arResult['ROWS'][$index] = [
		'id' => $key,
		'columns' => array_map(
			static fn(mixed $value): mixed => is_string($value) ? htmlspecialcharsbx($value) : $value,
			$data
		),
		'actions' => $actions
	];
}

$grid = (new \Shef\Options\Components\Builder('bitrix:main.ui.grid'))
	->setTemplate('')
	->setOptionCollection([
		'GRID_ID' => $arParams['GRID_ID'],
		'COLUMNS' => $arResult['COLUMNS'],
		'ROWS' => $arResult['ROWS'],
		'~NAV_PARAMS' => ['SHOW_ALWAYS' => false],
		'SHOW_ROW_CHECKBOXES' => false,
		'SHOW_GRID_SETTINGS_MENU' => true,
		'SHOW_PAGINATION' => false,
		'SHOW_SELECTED_COUNTER' => false,
		'SHOW_ROW_ACTIONS_MENU' => true,
		'SHOW_TOTAL_COUNTER' => true,
		'TOTAL_ROWS_COUNT' => $arResult['TOTAL_ROWS_COUNT'],
		'ALLOW_COLUMNS_SORT' => false,
		'ALLOW_COLUMNS_RESIZE' => false,
		'AJAX_MODE' => 'Y',
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_STYLE' => 'N',
		'AJAX_OPTION_HISTORY' => 'N'
	])
	->setIsActive(true)
	->setIsHideIcons(true)
;
// endregion ////
$this->SetViewTarget('pagetitle', 100);
?>
	<button type="button" class="ui-btn ui-btn-icon-info ui-btn-light-border ui-btn-themes" onclick="BX.ShInSync.ImportStat.reload(event);">
		<span><?=Loc::getMessage('BTN_RELOAD');?></span>
	</button>
<?php $this->EndViewTarget();?>
<div id="sh-template" class="sh-insync-grid">
	<?php if(count($arResult['ERRORS']) > 0):?>
	<div class="sh-insync-col sh-insync-col-full">
		<?php foreach($arResult['ERRORS'] as $error):
			$showAlert((string)$error, 'ui-alert-danger');
		endforeach;?>
	</div>
	<?php endif;?>
	<div class="sh-insync-col sh-insync-col-main">
		<?php $grid->include();?>
		<div class="sh-info">
			<?php $showAlert(implode(PHP_EOL, [
				Loc::getMessage('NOTE_TBL_IMPORT'),
				'',
				Loc::getMessage('NOTE_LIST_AGENTS'),
			]));?>
		</div>
	</div>
	<div class="sh-insync-col sh-insync-col-main">
		<div id="<?=$confJs['agentListId'];?>">
			<?php include 'agentList.php';?>
		</div>
	</div>
</div>
<script>
	BX.ready(() => {
		BX.ShInSync.ImportStat = BX.ShInSync.ImportStatController.create(<?=Web\Json::encode($confJs)?>);
		BX.ShInSync.UIAnchorsController.init();
	});
</script>