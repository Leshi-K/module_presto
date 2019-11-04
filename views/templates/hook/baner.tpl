{if $baners}
<div class="row">
	{foreach from=$baners key=key item=baner}
		<div class="col-xs-12 col-sm-4 col-md-4 col-lg-12">
			<div style="width: 200px; height: 113px; background-color: #eee; margin-bottom: 30px; position: relative;">
				<a href="{$baner.url}"><img src="{$baner.image_url}" class="img-responsive"></a>
				<p style="position: absolute; left: 10px; top: 10px; width: 100%; font-weight: bold;">{$baner.description nofilter}</p>
			</div>
		</div>
	{/foreach}
</div>
{/if}
