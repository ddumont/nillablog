<?php
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
 */

$PluginInfo["NillaBlog"] = array(
    "Name" => "${project.plugin.name}",
    "Description" => "${project.description}",
    "Version" => "${project.version}",
    "Author" => "${project.author.name}",
    "AuthorEmail" => "${project.author.email}",
    "AuthorUrl" => "${project.url}",
    "SettingsUrl" => "/dashboard/settings/nillablog",
    "SettingsPermission" => "Garden.Settings.Manage",
    "RequiredApplications" => array("Vanilla" => "2.0.18") // This needs to be bumped when Vanilla releases with my contributed changes
);

/**
 * NillaBlog plugin for Vanilla
 * @author ddumont@gmail.com
 */
class NillaBlogPlugin extends Gdn_Plugin {
    /**
     * Build the setting page.
     * @param $sender
     */
    public function settingsController_nillaBlog_create($sender) {
        $sender->permission('Garden.Settings.Manage');

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField(array("Plugins.NillaBlog.CategoryIDs" => []));
        $configurationModel->setField("Plugins.NillaBlog.DisableCSS");
        $configurationModel->setField("Plugins.NillaBlog.PostsPerPage");
        $configurationModel->setField("Plugins.NillaBlog.GooglePlusOne");
        $sender->Form->setModel($configurationModel);

        if ($sender->Form->authenticatedPostBack() === false) {
            $sender->Form->setData($configurationModel->Data);
        } else {
            $data = $sender->Form->formValues();
//            $configurationModel->Validation->ApplyRule("Plugins.NillaBlog.CategoryIDs", "RequiredArray");  // Not required
            $configurationModel->Validation->applyRule("Plugins.NillaBlog.PostsPerPage", "Integer");
            if ($sender->Form->save() !== false) {
                $sender->informMessage(t("Your settings have been saved."));
            }
        }

        $sender->setHighlightRoute('dashboard/settings');
        $sender->setData("Title", T("NillaBlog Settings"));

        $categoryModel = new CategoryModel();
        $sender->setData("CategoryData", $categoryModel->getAll(), true);
        array_shift($sender->CategoryData->result());

        $sender->render("settings", "", "plugins/NillaBlog");
    }

    /**
     * Adjusts the number of posts to display in the blog category.
     *
     * @param CategoriesController $sender Instance of the calling class.
     *
     * @return void.
     */
    public function categoriesController_beforeGetDiscussions_handler($sender) {
        if (!in_array($sender->CategoryID, c("Plugins.NillaBlog.CategoryIDs"))) {
            return;
        }
        $sender->EventArguments['PerPage'] = c("Plugins.NillaBlog.PostsPerPage");
    }

    /**
     * Insert the first comment under the discussion title for the blog category.
     *
     * This turns the blog category into a list of blog posts.
     *
     * @param CategoriesController $sender Instance of the calling class.
     *
     * @return void.
     */
    public function categoriesController_afterDiscussionTitle_handler($sender) {
        if (!in_array($sender->CategoryID, c("Plugins.NillaBlog.CategoryIDs"))) {
            return;
        }

        $discussion = $sender->EventArguments['Discussion'];

        $body = $discussion->Body;
        $end = strrpos($body, "<hr");
        if ($end) {
            $body = substr($body, 0, $end);
        }
        $formatBody = Gdn_Format::To($body, $discussion->Format);
        ?>
            <ul class="MessageList">
                <li>
                    <div class="Message">
                        <?php echo $formatBody; ?>
                    </div>
                </li>
                <?php if ($end) : ?>
                <li>
                    <?php
                    echo anchor(
                        t("Read more"),
                        discussionUrl($discussion),
                        ["class" => "More"]
                    );
                    ?>
                </li>
                <?php endif; ?>
            </ul>
        <?php
    }

    /**
     * Adds the blog subscription link to each post for easier access.
     *
     * @param CategoriesController $sender Instance of the calling class.
     *
     * @return void.
     */
    public function categoriesController_discussionMeta_handler($sender) {
        if (!in_array($sender->CategoryID, c("Plugins.NillaBlog.CategoryIDs"))) {
            return;
        }

        $discussion = $sender->EventArguments['Discussion'];
        ?>
        <span class="RSS">
            <a href="<?php echo url(concatSep('/', $sender->SelfUrl, 'feed.rss')); ?>">
                <img src="<?php echo asset("/applications/dashboard/design/images/rss.gif"); ?>"></img>
                <?php echo t("Subscribe to this blog"); ?>
            </a>
        </span>
        <?php

        if (!c("Plugins.NillaBlog.GooglePlusOne")) {
            return;
        }
        ?>
        <span class="plusone">
            <g:plusone href="<?php echo discussionUrl($discussion); ?>" size="medium">
            </g:plusone>
        </span>
        <?php
    }

    /**
     * Adds the class 'NillaBlog' to every discussion in the blog category list.
     *
     * Allows for themes to style the blog independently of the plugin.
     *
     * @param CategoriesController $sender Instance of the calling class.
     *
     * @return void.
     */
    public function categoriesController_beforeDiscussionName_handler($sender) {
        if (!in_array($sender->CategoryID, c("Plugins.NillaBlog.CategoryIDs"))) {
            return;
        }
        $sender->EventArguments["CssClass"] .= " NillaBlog NillaBlog".$sender->CategoryID." ";
    }

    /**
     * Adds the class 'NillaBlog' to every comment (including the first post)
     * in the blog category list.
     *
     * Allows for themes to style the blog independently of the plugin.
     *
     * @param DiscussionController $sender Instance of the calling class.
     *
     * @return void.
     */
    public function discussionController_beforeCommentDisplay_handler($sender) {
        if (!in_array($sender->CategoryID, c("Plugins.NillaBlog.CategoryIDs"))) {
            return;
        }
        $sender->EventArguments["CssClass"] .= " NillaBlog NillaBlog".$sender->CategoryID." ";
    }

    /**
     * Sorts blog posts by creation time rather than last comment.
     *
     * @param DiscussionModel $sender Instance of the calling class.
     *
     * @return void.
     */
    public function discussionModel_beforeGet_handler($sender) {
        $wheres = $sender->EventArguments["Wheres"];
        if (
            !array_key_exists("d.CategoryID", $wheres) ||
            !in_array($wheres["d.CategoryID"], c("Plugins.NillaBlog.CategoryIDs"))
        ) {
            return;
        }

        $sender->EventArguments["SortField"] = "d.DateInserted";
        $sender->EventArguments["SortDirection"] = "desc";
    }

    /**
     * Insert default CSS into the discussion list for the blog.
     *
     * @param CategoriesController $sender Instance of the calling class.
     *
     * @return void.
     */
    public function categoriesController_render_before($sender) {
        if (
            !in_array($sender->CategoryID, c("Plugins.NillaBlog.CategoryIDs")) ||
            c("Plugins.NillaBlog.DisableCSS")
        ) {
            return;
        }

        if (c("Plugins.NillaBlog.GooglePlusOne")) {
            $sender->addJsFile('http://apis.google.com/js/plusone.js');
        }

        $sender->addCssFile($this->getResource('design/custom.css', false, false));
    }

    /**
     * Insert default CSS into the comment list for the blog discussion.
     *
     * @param DiscussionController $sender Instance of the calling class.
     *
     * @return void.
     */
    public function discussionController_render_before($sender) {
        if (
            !in_array($sender->CategoryID, c("Plugins.NillaBlog.CategoryIDs")) ||
            c("Plugins.NillaBlog.DisableCSS")
        ) {
            return;
        }

        if (c("Plugins.NillaBlog.GooglePlusOne")) {
            $sender->addJsFile('http://apis.google.com/js/plusone.js');
        }

        $sender->addCssFile($this->getResource('design/custom.css', false, false));
    }

    /**
     * Insert a clickable comments link appropriate for the blog.  We'll hide the other comment count with CSS.
     *
     * @param CategoriesController $sender Instance of the calling class.
     *
     * @return void.

     */
    public function categoriesController_beforeDiscussionMeta_handler($sender) {
        if (
            !in_array($sender->CategoryID, c("Plugins.NillaBlog.CategoryIDs")) ||
            c("Plugins.NillaBlog.DisableCSS")
        ) {
            return;
        }

        $discussion = $sender->EventArguments['Discussion'];
        $count = $discussion->CountComments - 1;
        $label = sprintf(plural($count, '%s comment', '%s comments'), $count);
        ?>
        <span class="CommentCount NillaBlog NillaBlog<?php echo $sender->CategoryID;?>>">
            <a href="<?php echo url(
            	concatSep(
            		"/",
            		"discussion",
            		$discussion->DiscussionID,
            		Gdn_Format::url($discussion->Name).($count > 0 ? "#Item_2" : "")
            	)
            );
            ?>">
                <?php echo $label; ?>
            </a>
        </span>
        <?php
    }

}
