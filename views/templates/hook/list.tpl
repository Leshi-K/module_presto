<div class="panel"><h3><i class="icon-list-ul"></i> Items list
	<span class="panel-heading-action">
		<a id="desc-product-new" class="list-toolbar-btn" href="{$link->getAdminLink('AdminModules')}&configure=leshthreebaners&addItem=1">
			<span title="" data-toggle="tooltip" class="label-tooltip" data-original-title="Add new" data-html="true">
				<i class="process-icon-new "></i>
			</span>
		</a>
	</span>
	</h3>
	<div id="slidesContent">
		<div id="items">
			{foreach from=$items item=item}

				<div id="items_{$item.id_leshthreebaner}" class="panel">
					<div class="row">
						<div class="col-lg-1">
							<span><i class="icon-arrows "></i></span>
						</div>
						<div class="col-md-3">
							<img src="{$image_baseurl}{$item.image}" alt="" class="img-thumbnail" />
						</div>
						<div class="col-md-8">
							<div class="btn-group-action pull-right">
								{$item.status}

								<a class="btn btn-default"
									href="{$link->getAdminLink('AdminModules')}&configure=leshthreebaners&id_item={$item.id_leshthreebaner}">
									<i class="icon-edit"></i>
									Edit
								</a>
								<a class="btn btn-default"
									href="{$link->getAdminLink('AdminModules')}&configure=leshthreebaners&delete_id_item={$item.id_leshthreebaner}">
									<i class="icon-trash"></i>
									Delete
								</a>
							</div>
						</div>
					</div>
				</div>
			{/foreach}
		</div>
	</div>
</div>
