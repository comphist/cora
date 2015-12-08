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
?>

<div id="helpDiv" class="content">
  <div class="panel">
    <div class="text-content">
      <h3 data-trans-id="Help.coraHelpTitle"><?=$lh("Help.coraHelpTitle"); ?></h3>
      <p data-trans-id="Help.helpInGuide">
        <?=$lh("Help.helpInGuide"); ?>
      </p>
      <ul>
        <li>
          <a href="doc/cora-guide.pdf" download="cora-guide.pdf" data-trans-id="Help.coraGuideLink"><?=$lh("Help.coraGuideLink"); ?></a>
        </li>
      </ul>

      <h3 data-trans-id="Help.feedbackTitle"><?=$lh("Help.feedbackTitle"); ?></h3>
      <p>
        <span data-trans-id="Help.contactUsInfo"><?=$lh("Help.contactUsInfo"); ?></span>
        <a href="mailto:bollmann@linguistics.rub.de" data-trans-id="Help.contactUs"><?=$lh("Help.contactUs"); ?></a>
      </p>
      <p data-trans-id="Help.contactRequest">
        <?=$lh("Help.contactRequest"); ?>    
      </p>
      <ul>
        <li data-trans-id="Help.contactCheck1"><?=$lh("Help.contactCheck1"); ?></li>
        <li data-trans-id="Help.contactCheck2"><?=$lh("Help.contactCheck2"); ?></li>
        <li data-trans-id="Help.contactCheck3"><?=$lh("Help.contactCheck3"); ?></li>
      </ul>

      <p>
        <span data-trans-id="Help.contactPerson"><?=$lh("Help.contactPerson"); ?></span>
        <a href="mailto:bollmann@linguistics.rub.de">Marcel Bollmann &lsaquo;bollmann@linguistics.rub.de&rsaquo;</a>
      </p>
    </div>
  </div>
</div>
