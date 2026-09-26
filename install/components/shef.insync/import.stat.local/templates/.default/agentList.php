<?php declare(strict_types=1);

defined('B_PROLOG_INCLUDED') || die;

use Shef\InSync\Main\Access;

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

/** @var Shef\Options\Options\SmartStd $agentEntity */
/** @var \Shef\InSync\Agents\Entity $agentInfo */
/** @var \Shef\InSync\Agents\Entity $agentRel */

$groups = [];
foreach($arResult['AGENTS_LIST'] as $agentEntity)
{
	$agentInfo = $agentEntity->entity;
	$agentEntity->title = $agentInfo->getTitle();

	$groups[$agentInfo->getOrigin()][] = $agentEntity;
}

foreach($groups as $group => $list):?>
	<div class="sh-insync-card sh-insync-agents">
	<?php foreach($list as $agentEntity):
		$agentInfo = $agentEntity->entity;
		// Кнопки — только тому, у кого права на модуль агента; действие
		// проверяет их ещё раз.
		$canManage = Access::canManage($agentInfo->getModule());
		?>
		<div class="sh-insync-card-header" title="<?=htmlspecialcharsbx($agentInfo->getModule().' | '.$agentInfo->getOrigin())?>">
			<?=htmlspecialcharsbx((string)$agentEntity->title)?>
			<?php // Описание пишет разработчик агента — это разметка, а не данные. ?>
			<?=$agentInfo->getDescription()?>
		</div>
		<div class="sh-insync-card-body">
		<?php foreach($agentEntity->list as $agentRel):?>
			<div class="sh-insync-agent">
				<a href="/bitrix/admin/agent_edit.php?ID=<?=(int)$agentRel->getId()?>&lang=<?=LANGUAGE_ID?>" target="_blank">
					<?php if($agentRel->isActive()):?>
						<?=$agentRel->getNextExec()?->format('d.m.Y H:i:s');?>
					<?php else:?>
						<?=htmlspecialcharsbx($agentRel->getName())?>
					<?php endif;?>
				</a>
				<?php if($canManage):?>
					<?php if($agentRel->isActive()):?>
						<button
							onclick="BX.ShInSync.ImportStat.onStopAgent(event, <?=(int)$agentRel->getId();?>)"
							class="ui-btn ui-btn-light ui-btn-xs ui-btn-icon-pause"
							type="button"
						></button>
					<?php else:?>
						<button
							onclick="BX.ShInSync.ImportStat.onStartAgent(event, <?=(int)$agentRel->getId();?>);"
							type="button"
							class="ui-btn ui-btn-success ui-btn-xs ui-btn-icon-start"
						></button>
					<?php endif;?>
				<?php endif;?>
			</div>
		<?php endforeach;?>
		</div>
	<?php endforeach;?>
	</div>
<?php endforeach;
