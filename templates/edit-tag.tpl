{include file="header"}

<div class="edit">

	{if $row['tagid']}
		<h2>
			<span class="assettype">
				<a href="/list/tag">Tags</a>
			</span> / 
			<span class="title">
				{$row["name"]}
			</span>
		</h2>	
	{else}
		<h2>
			<span class="assettype">
				<a href="/list/tag">Tags</a>
			</span> / 
			<span class="title">Add new Tag</span>
		</h2>
	{/if}

	<form method="post" name="deleteform">
		<input type="hidden" name="delete" value="1">
	</form>

	<form method="post" name="form1">
		<input type="hidden" name="save" value="1">
		<input type="hidden" name="saveandback" value="0">
		
		<div class="editbox linebreak">
			<label>Name</label>
			<input type="text" name="name" class="required" value="{$row['name']}"/>
		</div>
		
		<div class="editbox linebreak">
			<label>Description</label>
			<textarea name="text" style="width: 600px; height: 80px;">{$row['text']}</textarea>
		</div>
		
		<div class="editbox linebreak">
			<label>Color</label>
			<input type="text" class="color" name="code" class="required" value="{$row['color']}"/>
		</div>
		
		<!--<div class="editbox linebreak">
			<label>Tag Type</label>
			<select style="width:200px;" name="tagtypeid" class="required">
				<option value="">-</option>
				{foreach from=$tagtypes item=tagtype}
					<option value="{$tagtype['tagtypeid']}" {if $tagtype['tagtypeid']==$row['tagtypeid']}selected="selected"{/if}>{$tagtype['name']}</option>
				{/foreach}
			</select>
		</div>-->
		
		<div class="editbox linebreak">
			<label>For Asset Types</label>
			<select style="width:200px;" name="assettypeid" class="required">
				<option value="">-</option>
				{foreach from=$assettypes item=assettype}
					<option value="{$assettype['assettypeid']}" {if $assettype['assettypeid']==$row['assettypeid']}selected="selected"{/if}>{$assettype['name']}</option>
				{/foreach}
			</select>
		</div>
	</form>
</div>

{capture name="buttons"}
	
	{include
		file="button"
		href="javascript:submitForm(0)"
		buttontext="Save"
	}

	{include
		file="button"
		href="javascript:submitForm(1)"
		buttontext="Save+Back"
	}
	
	{if $row['tagid']}
		<p style="clear:both;"><br></p>
		{include
			file="button"
			href="javascript:submitDelete()"
			buttontext="Delete Tag"
		}
	{/if}
{/capture}

{capture name="footerjs"}
<script type="text/javascript" src="/web/js/jQueryColorPicker.min.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
	
	/*	$myColorPicker = $('input.color').colorPicker({
			customBG: '#222',
			readOnly: true,
			init: function(elm, colors) {
				elm.style.backgroundColor = elm.value;
				elm.style.color = colors.rgbaMixCustom.luminance > 0.22 ? '#222' : '#ddd';
			}
		});
	});*/
</script>
<script type="text/javascript">

	function submitForm(returntolist) {
		$('form[name=form1]').trigger('reinitialize.areYouSure');
		
		if (returntolist) {
			$('input[name="saveandback"]').val(1);
		}
		document['form1'].submit();
	}
	
	function submitDelete() {
		if (confirm("Really delete this entry?")) {
			document['deleteform'].submit();
		}
	}
	
</script>
{/capture}

{include file="footer"}
