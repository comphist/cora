<?php 
/*
 * Copyright (C) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */ ?>
<?php
/** @file help.php
 * A help page.
 */

$maintainer = Cfg::get('local_maintainer');
?>

<div id="helpDiv" class="content">
  <div class="panel">
    <div class="text-content">
      <h3 data-trans-id="Help.title"><?=$lh("Help.title"); ?></h3>
      <p data-trans-id="Help.paragraphGuide">
        <?=$lh("Help.paragraphGuide"); ?>
      </p>
      <ul>
        <li>
          <a href="http://cora.readthedocs.org/" data-trans-id="Help.guideTitle"><?=$lh("Help.guideTitle"); ?></a>
        </li>
      </ul>

      <h3 data-trans-id="Help.feedbackTitle"><?=$lh("Help.feedbackTitle"); ?></h3>
      <p data-trans-id="Help.paragraphFeedback">
        <?=$lh("Help.paragraphFeedback"); ?>
      </p>
      <ul>
        <li data-trans-id="Help.contactCheck1"><?=$lh("Help.contactCheck1"); ?></li>
        <li data-trans-id="Help.contactCheck2"><?=$lh("Help.contactCheck2"); ?></li>
        <li data-trans-id="Help.contactCheck3"><?=$lh("Help.contactCheck3"); ?></li>
      </ul>

      <p data-trans-id="Help.paragraphAskAnAdmin">
        <?=$lh("Help.paragraphAskAnAdmin"); ?>
      </p>

      <?php if (isset($maintainer['name']) || isset($maintainer['email'])):
                $contact_info = "";
                if (isset($maintainer['name'])):
                    $contact_info = $maintainer['name'] . " ";
                endif;
                if (isset($maintainer['email'])):
                    $contact_info = "<a href=\"mailto:" . $maintainer['email'] .
                                    "\">" . $contact_info .
                                    "&lsaquo;" . $maintainer['email'] . "&rsaquo;</a>";
                endif;
      ?>
        <p>
          <span data-trans-id="Help.paragraphAdminIs"><?=$lh("Help.paragraphAdminIs"); ?></span>
          <ul>
            <li><?=$contact_info;?></li>
          </ul>
        </p>
      <?php endif; ?>

      <?php if ($_SESSION["admin"]): ?>
        <p data-trans-id="Help.paragraphUseTheIssueTracker"><?=$lh("Help.paragraphUseTheIssueTracker"); ?></p>
        <ul>
          <li><a href="https://bitbucket.org/mbollmann/cora/issues" data-trans-id="Help.issueTracker">
            <?=$lh("Help.issueTracker"); ?>
          </a></li>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>
