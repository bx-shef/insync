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
	'ajax',
	'shef-uiclear.bx-loader',
	'shef-uiclear.bootstrap',
	'shef-uiclear.grid',
	'shef-uiclear.bootstrap-card',
	'shef-insync.ui-anchors'
]);

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
	
	$arResult['ROWS'][$index] = [
		'id' => $key,
		'columns' => $data,
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
<div id="sh-template" class="g-5">
	<?php if(count($arResult['ERRORS']) > 0):?>
	<div class="row g-5 mb-4"><div class="col-12 order-0">
		<?php foreach($arResult['ERRORS'] as $error):
			_showError($error);
		endforeach;?>
	</div></div>
	<?php endif;?>
	<div class="row mb-5 g-5">
		<div class="col-md-6 col-sm-12 order-0">
			<?php $grid->include();?>
			<div class="sh-info mt-5">
				<?php _showError(implode(PHP_EOL, [
					Loc::getMessage('NOTE_TBL_IMPORT'),
					'',
					Loc::getMessage('NOTE_LIST_AGENTS'),
				]), 'warningtext');?>
			</div>
		</div>
		<div class="col-md-6 col-sm-12 order-1">
			<div id="<?=$confJs['agentListId'];?>">
				<?php include 'agentList.php';?>
			</div>
			
		</div>
	</div>
</div>
<script>
	BX.ready(() => {
		BX.ShInSync.ImportStat = BX.ShInSync.ImportStatController.create(<?=Web\Json::encode($confJs)?>);
		BX.ShInSync.UIAnchorsController.init();
	});
</script>