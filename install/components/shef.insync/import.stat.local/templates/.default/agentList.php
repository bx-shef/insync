<?php declare(strict_types=1);

defined('B_PROLOG_INCLUDED') || die;

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

$firstOrigin = null;
$groups = [];
foreach($arResult['AGENTS_LIST'] as $agentEntity)
{
	$agentInfo = $agentEntity->entity;
	$agentEntity->title = $agentInfo->getTitle();
	
	if(!isset($groups[$agentInfo->getOrigin()]))
	{
		$groups[$agentInfo->getOrigin()] = [];
	}
	
	$groups[$agentInfo->getOrigin()][] = $agentEntity;
}

foreach($groups as $group => $list):
	
	$isFirst = true;
	$cnt = count($list) - 1;
	
	foreach($list as $index => $agentEntity):
		$agentInfo = $agentEntity->entity;
		
		$class = [];
		if($isFirst)
		{
			$class[] = 'rounded-0';
			$class[] = 'rounded-top';
			
			$isFirst = false;
		}
		else
		{
			$class[] = 'rounded-0';
		}
		
		if($index === $cnt)
		{
			$class[] = 'rounded-bottom';
			$class[] = 'mb-5';
		}
		
		?>
		<div class="card <?=join(' ', $class)?>">
			<div class="card-header">
				<div class="card-title my-1" title="<?=$agentInfo->getModule()?> | <?=$agentInfo->getOrigin()?> "><?=$agentEntity->title?> <?=$agentInfo->getDescription()?></div>
			</div>
			<?php
			foreach($agentEntity->list as $agentRel):?>
				<div class="list-group"><div class="row py-2 px-4">
					<div class="col-10">
						<div class="row p-0">
							<div class="col-12"><a href="/bitrix/admin/agent_edit.php?ID=<?=$agentRel->getId()?>" target="_blank">
								<?php if($agentRel->isActive()):?>
									<?=$agentRel->getNextExec()?->format('d.m.Y H:i:s');?>
								<?php else:?>
									<?=$agentRel->getName()?>
								<?php endif;?></a>
							</div>
						</div>
					</div>
					<div class="col-2 text-end">
						<?php if($agentRel->isActive()):?>
							<button
								onclick="BX.ShInSync.ImportStat.onStopAgent(event, <?=$agentRel->getId();?>)"
								class="ui-btn ui-btn-light ui-btn-xs ui-btn-icon-pause"
								type="button"
							></button>
						<?php else:?>
							<button
								onclick='BX.ShInSync.ImportStat.onStartAgent(event, <?=$agentRel->getId();?>);'
								type="button"
								class="ui-btn ui-btn-success ui-btn-xs ui-btn-icon-start"
							></button>
						<?php endif;?>
					</div>
				</div></div>
			<?php endforeach;?>
		</div>
	<?php endforeach;?>
<?php endforeach;?>