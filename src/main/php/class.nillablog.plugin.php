<?php if (!defined("APPLICATION")) exit();
/*
 *  Nillablog vanilla plugin.
 *  Copyright (C) 2011 ddumont@gmail.com
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

$PluginInfo["NillaBlog"] = array(
	"Name" => "NillaBlog",
	"Description" => "A blog plugin for vanilla 2+",
	"Version" => "1.8.1",
	"Author" => "Dan Dumont",
	"AuthorEmail" => "ddumont@gmail.com",
	"SettingsUrl" => "/dashboard/settings/nillablog",
	"SettingsPermission" => "Garden.Settings.Manage",
	"AuthorUrl" => "https://github.com/ddumont/nillablog",
	"RequiredApplications" => array("Vanilla" => "2.0.18") // This needs to be bumped when Vanilla releases with my contributed changes
);

/**
 * NillaBlog plugin for Vanilla
 * @author ddumont@gmail.com
 */
class NillaBlog extends Gdn_Plugin {

	/**
	 * Build the setting page.
	 * @param $Sender
	 */
	public function SettingsController_NillaBlog_Create($Sender) {
		$Sender->Permission('Garden.Settings.Manage');

		$Validation = new Gdn_Validation();
		$ConfigurationModel = new Gdn_ConfigurationModel($Validation);
		$ConfigurationModel->SetField(array("Plugins.NillaBlog.CategoryIDs"));
		$ConfigurationModel->SetField("Plugins.NillaBlog.DisableCSS");
		$ConfigurationModel->SetField("Plugins.NillaBlog.PostsPerPage");
		$ConfigurationModel->SetField("Plugins.NillaBlog.GooglePlusOne");
		$Sender->Form->SetModel($ConfigurationModel);

		if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
			$Sender->Form->SetData($ConfigurationModel->Data);
		} else {
        	$Data = $Sender->Form->FormValues();
//        	$ConfigurationModel->Validation->ApplyRule("Plugins.NillaBlog.CategoryIDs", "RequiredArray");  // Not required
			$ConfigurationModel->Validation->ApplyRule("Plugins.NillaBlog.PostsPerPage", "Integer");
        	if ($Sender->Form->Save() !== FALSE)
        		$Sender->StatusMessage = T("Your settings have been saved.");
		}

		$Sender->AddSideMenu();
		$Sender->SetData("Title", T("NillaBlog Settings"));

		$CategoryModel = new CategoryModel();
		$Sender->SetData("CategoryData", $CategoryModel->GetAll(), TRUE);
		array_shift($Sender->CategoryData->Result());

		$Sender->Render($this->GetView("settings.php"));
	}

	/**
	 * Adjusts the number of posts to display in the blog category.
	 * @param $Sender
	 */
	public function CategoriesController_BeforeGetDiscussions_Handler($Sender) {
		if ( !in_array($Sender->CategoryID, C("Plugins.NillaBlog.CategoryIDs")) )
			return;
		$Sender->EventArguments['PerPage'] = C("Plugins.NillaBlog.PostsPerPage");
	}

	/**
	 * Insert the first comment under the discussion title for the blog category.
	 * This turns the blog category into a list of blog posts.
	 * @param $Sender
	 */
	public function CategoriesController_AfterDiscussionTitle_Handler($Sender) {
		if ( !in_array($Sender->CategoryID, C("Plugins.NillaBlog.CategoryIDs")) )
			return;

		$Discussion = $Sender->EventArguments['Discussion'];

		$Body = $Discussion->Body;
		$end = strrpos($Body, "<hr");
		if ($end)
			$Body = substr($Body, 0, $end);
		$Discussion->FormatBody = Gdn_Format::To($Body, $Discussion->Format);
		?>
			<ul class="MessageList">
				<li>
					<div class="Message">
						<?php echo $Discussion->FormatBody; ?>
					</div>
				</li>
				<?php if ($end) { ?>
					<li>
						<a href="<?php echo Gdn::Request()->Url(ConcatSep("/", "discussion", $Discussion->DiscussionID, Gdn_Format::Url($Discussion->Name)))?>"
						   class="More"><?php echo T("Read more");?></a>
					</li>
				<?php } ?>
			</ul>
		<?php
	}

	/**
	 * Adds the blog subscription link to each post for easier access.
	 * @param $Sender
	 */
	public function CategoriesController_DiscussionMeta_Handler($Sender) {
		if ( !in_array($Sender->CategoryID, C("Plugins.NillaBlog.CategoryIDs")) )
			return;

		$Discussion = $Sender->EventArguments['Discussion'];
		?>
			<span class='RSS'>
				<a href='<?php echo Gdn::Request()->Url(ConcatSep("/", $Sender->SelfUrl, "feed.rss")); ?>'>
					<img src="<?php echo Asset("/applications/dashboard/design/images/rss.gif"); ?>"></img>
					<?php echo T("Subscribe to this blog"); ?>
				</a>
			</span>
		<?php

		if (C("Plugins.NillaBlog.GooglePlusOne")) {
			?><span class='plusone'>
				<g:plusone href="<?php echo Gdn::Request()->Url(ConcatSep("/", "discussion", $Discussion->DiscussionID, Gdn_Format::Url($Discussion->Name)), TRUE);
					?>" size="medium">
				</g:plusone>
			</span><?php
		}
	}

	/**
	 * Adds the class 'NillaBlog' to every discussion in the blog category list.
	 * Allows for themes to style the blog independently of the plugin.
	 * @param $Sender
	 */
	public function CategoriesController_BeforeDiscussionName_Handler($Sender) {
		if ( !in_array($Sender->CategoryID, C("Plugins.NillaBlog.CategoryIDs")) )
			return;
		$Sender->EventArguments["CssClass"] .= " NillaBlog NillaBlog".$Sender->CategoryID." ";
	}

	/**
	 * Adds the class 'NillaBlog' to every comment (including the first post) in the blog category list.
	 * Allows for themes to style the blog independently of the plugin.
	 * @param $Sender
	 */
	public function DiscussionController_BeforeCommentDisplay_Handler($Sender) {
		if ( !in_array($Sender->CategoryID, C("Plugins.NillaBlog.CategoryIDs")) )
			return;
		$Sender->EventArguments["CssClass"] .= " NillaBlog NillaBlog".$Sender->CategoryID." ";
	}

	/**
	 * Sorts blog posts by creation time rather than last comment.
	 * @param $Sender
	 */
	public function DiscussionModel_BeforeGet_Handler($Sender) {
		$Wheres = $Sender->EventArguments["Wheres"];
		if (!array_key_exists("d.CategoryID", $Wheres) || !in_array($Wheres["d.CategoryID"], C("Plugins.NillaBlog.CategoryIDs")))
			return;

		$Sender->EventArguments["SortField"] = "d.DateInserted";
		$Sender->EventArguments["SortDirection"] = "desc";
	}

	/**
	 * Insert default CSS into the discussion list for the blog.
	 * @param $Sender
	 */
	public function CategoriesController_Render_Before($Sender) {
		if ( !in_array($Sender->CategoryID, C("Plugins.NillaBlog.CategoryIDs")) || C("Plugins.NillaBlog.DisableCSS") )
			return;

		if (C("Plugins.NillaBlog.GooglePlusOne"))
			$Sender->AddJsFile('http://apis.google.com/js/plusone.js');

		$Sender->AddCssFile($this->GetResource('design/custom.css', FALSE, FALSE));
	}

	/**
	 * Insert default CSS into the comment list for the blog discussion.
	 * @param $Sender
	 */
	public function DiscussionController_Render_Before($Sender) {
		if ( !in_array($Sender->CategoryID, C("Plugins.NillaBlog.CategoryIDs")) || C("Plugins.NillaBlog.DisableCSS") )
			return;

		if (C("Plugins.NillaBlog.GooglePlusOne"))
			$Sender->AddJsFile('http://apis.google.com/js/plusone.js');

		$Sender->AddCssFile($this->GetResource('design/custom.css', FALSE, FALSE));
	}

	/**
	 * Insert a clickable comments link appropriate for the blog.  We'll hide the other comment count with CSS.
	 * @param $Sender
	 */
	public function CategoriesController_BeforeDiscussionMeta_Handler($Sender) {
		if ( !in_array($Sender->CategoryID, C("Plugins.NillaBlog.CategoryIDs")) || C("Plugins.NillaBlog.DisableCSS") )
			return;

		$Discussion = $Sender->EventArguments['Discussion'];
		$Count = $Discussion->CountComments - 1;
		$Label = sprintf(Plural($Count, '%s comment', '%s comments'), $Count);
		?>
			<span class="CommentCount NillaBlog NillaBlog<?php echo $Sender->CategoryID;?>>">
				<a href="<?php
					echo Gdn::Request()->Url(ConcatSep("/", "discussion", $Discussion->DiscussionID, Gdn_Format::Url($Discussion->Name).($Count > 0 ? "#Item_2" : "")));
				?>">
					<?php echo $Label; ?>
				</a>
			</span>
		<?php
	}

	public function Setup() {}
}