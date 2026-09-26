<?php declare(strict_types=1);

defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web;
use Bitrix\Main\IO;

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
/** @var \Local\Component\Shef\InSync\ShefInSyncImportFromFileComponent $component */

// Только штатные расширения ядра: разметка и загрузчик из shef.uiclear
// (bootstrap, карточки, его Loader) ушли вместе с зависимостью.
Extension::load([
	'ui.forms',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.notification',
	'main.loader',
	'ajax',
	'shef-insync.ui-anchors'
]);

Loc::loadMessages(__FILE__);

$confJs = [
	'formId' => 'formImport',
	'responseId' => 'formImportResponse',
	'responseErrorId' => 'formImportResponseError',
	'component' => 'shef.insync:import.from.file',
	'mode' => 'class',
	'signedParameters' => $this->getComponent()->getSignedParameters(),
	'module' => (string)$arParams['MODULE'],
	'className' => $component->getObjectProcess()::class,
];

if(($demoFile = $component->getObjectProcess()::getDemoFile()) instanceof IO\File):
	$this->SetViewTarget('pagetitle', 100);
?>
	<button
		type="button"
		onclick="BX.ShInSync.ImportFromFile.getDemoFile(event);"
		class="ui-btn ui-btn-icon-download ui-btn-light-border ui-btn-themes"
	><?=Loc::getMessage('BTN_DEMO_FILE');?></button>
<?php
	$this->EndViewTarget();
endif;?>

<div id="sh-template" class="sh-insync-grid">
	<div class="sh-insync-col sh-insync-col-side">
		<form
			enctype="multipart/form-data"
			method="post"
			id="<?=$confJs['formId'];?>"
			class="sh-insync-card"
		>
			<?=bitrix_sessid_post();?>
			<div class="sh-insync-card-header"><?=Loc::getMessage('TITLE_LOAD');?></div>
			<div class="sh-insync-card-body">
				<div class="ui-ctl ui-ctl-file-drop ui-ctl-w100">
					<input
						type="file"
						class="ui-ctl-element"
						accept="<?=htmlspecialcharsbx($component->getObjectProcess()::getImportFileAccept())?>"
						name="<?=$arParams['INPUT_NAME']['FILE'];?>"
						id="<?=$arParams['INPUT_NAME']['FILE'];?>"
						required
						autofocus
					>
				</div>
			</div>
			<div class="sh-insync-card-footer">
				<button
					type="submit"
					class="ui-btn ui-btn-sm ui-btn-primary"
				><?=Loc::getMessage('BTN_LOAD');?></button>
			</div>
		</form>

		<div class="sh-insync-card">
			<div class="sh-insync-card-header"><?=Loc::getMessage('TITLE_DESCRIPTION');?></div>
			<div class="sh-insync-card-body">
				<?php // Описание пишет разработчик класса импорта — это разметка, а не данные. ?>
				<div class="sh-description"><?=$component->getObjectProcess()::getProcessDescription();?></div>
			</div>
		</div>
	</div>
	<div class="sh-insync-col sh-insync-col-main">
		<div class="sh-insync-card">
			<div class="sh-insync-card-header"><?=Loc::getMessage('TITLE_RESPONSE');?></div>
			<div class="sh-insync-card-body">
				<div id="<?=$confJs['responseId'];?>"></div>
			</div>
			<div class="sh-insync-card-footer">
				<div id="<?=$confJs['responseErrorId'];?>"></div>
			</div>
		</div>
	</div>
</div>
<script>
	BX.ready(() => {
		BX.ShInSync.ImportFromFile = BX.ShInSync.ImportFromFileController.create(<?=Web\Json::encode($confJs);?>);
		BX.ShInSync.UIAnchorsController.init();
	});
</script>
