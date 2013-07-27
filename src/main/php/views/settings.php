<?php if (!defined("APPLICATION")) exit(); 
/*
 *  (C) Copyright 2011, canofsleep.com
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */?>
<h1> <?php echo $this->Data("Title");?> </h1>
<?php
	echo $this->Form->Open();
	echo $this->Form->Errors();
?>
<ul>
	<li>
		<h3><?php echo T("Blog Categories"); ?></h3>
		<?php 
			echo $this->Form->CheckBoxList("Plugins.NillaBlog.CategoryIDs", $this->CategoryData, NULL, array(
				"TextField" => "Name", 
				"ValueField" => "CategoryID",
			));
		?>
	</li>
	<li>
		<h3><?php echo T("Blog Appearance"); ?></h3>
		<ul class='CheckBoxList'>
			<li>
				<?php
					echo $this->Form->Input("Plugins.NillaBlog.PostsPerPage", "input", array(
						"size" => "3",
						"maxlength" => "3",
						"style" => "text-align: center;"
					)); 
					echo $this->Form->Label("Blog posts per page ( Default: 10 )", "Plugins.NillaBlog.PostsPerPage", array(
						"class" => "CheckBoxLabel",
						"style" => "margin-left: 10px;", 
					)); 
				?>
			</li>
			<li>
				<?php 
					// The ul above is to match the style of the CheckBoxList in the section above.
					echo $this->Form->CheckBox(
						"Plugins.NillaBlog.DisableCSS", 
						T("Disable NillaBlog default style. (If installed theme will handle the style)")
					);
				 ?>
			</li>
		</ul>
	</li>
	<li>
		<h3><?php echo T("Miscellaneous"); ?></h3>
		<ul class='CheckBoxList'>
			<li>
				<?php 
					// The ul above is to match the style of the CheckBoxList in the section above.
					echo $this->Form->CheckBox(
						"Plugins.NillaBlog.GooglePlusOne", 
						T("Insert a Google +1 button in blog posts.")
					);
				 ?>
			</li>
		</ul>
	</li>
</ul>
<br>

<?php 
   echo $this->Form->Close("Save");

