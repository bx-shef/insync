<?php declare(strict_types=1);

defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
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

Extension::load([
	'ui.forms',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.notification',
	'ajax',
	'shef-uiclear.bx-loader',
	'shef-uiclear.bootstrap',
	'shef-uiclear.grid',
	'shef-uiclear.bootstrap-card',
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
];

if(($demoFile = $component->getObjectProcess()::getDemoFile()) instanceof IO\File):
	$this->SetViewTarget('pagetitle', 100);
?>
	<button
		type="button"
		onclick="BX.ShInSync.ImportFromFile.getDemoFile(event, '<?=$arParams['MODULE']?>', '<?=str_replace('\\', '\\\\', $component->getObjectProcess()::class)?>')"
		class="ui-btn ui-btn-icon-download ui-btn-light-border ui-btn-themes"
	><?=Loc::getMessage('BTN_DEMO_FILE');?></button>
<?php
	$this->EndViewTarget();
endif;?>

<div id="sh-template" class="g-5">
	<div class="row mb-5 g-5">
		<div class="col-lg-4 order-0">
			<form
				enctype="multipart/form-data"
				method="post"
				id="<?=$confJs['formId'];?>"
			>
				<?=bitrix_sessid_post();?>
				<div class="card">
					<div class="card-header">
						<h3 class="card-title my-1"><?=Loc::getMessage('TITLE_LOAD');?></h3>
					</div>
					<div class="card-body">
						<div class="ui-ctl">
							<input
								type="file"
								class="d-ui-ctl-element"
								accept="<?=$component->getObjectProcess()::getImportFileAccept()?>"
								name="<?=$arParams['INPUT_NAME']['FILE'];?>"
								id="<?=$arParams['INPUT_NAME']['FILE'];?>"
								required
								autofocus
							>
						</div>
					</div>
					<div class="card-footer">
						<button
							type="submit"
							class="ui-btn ui-btn-sm ui-btn-primary"
						><?=Loc::getMessage('BTN_LOAD');?></button>
					</div>
				</div>
			</form>
			
			<div class="card mt-5">
				<div class="card-header">
					<h3 class="card-title my-1"><?=Loc::getMessage('TITLE_DESCRIPTION');?></h3>
				</div>
				<div class="card-body">
					<div class="mb-5 sh-description"><?=$component->getObjectProcess()::getProcessDescription();?></div>
				</div>
			</div>
		</div>
		<div class="col-lg-8 order-1">
			<div class="col-12 order-0">
				<div class="card">
					<div class="card-header">
						<h3 class="card-title my-1"><?=Loc::getMessage('TITLE_RESPONSE');?></h3>
					</div>
					<div class="card-body">
						<div id="<?=$confJs['responseId'];?>"></div>
					</div>
					<div class="card-footer">
						<div id="<?=$confJs['responseErrorId'];?>"></div>
					</div>
				</div>
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