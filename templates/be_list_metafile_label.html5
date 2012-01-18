<div class="list_metafile_label">
    <div class="title <?php echo $this->class; ?>"><strong><?php echo $this->title; ?></strong>
		<span class="count">(<?php echo $this->countText; ?>)</span>
	</div>
	<div class="filename"><?php echo $this->TLFilename; ?></div>
    <?php if($this->isFile === false && $this->isPublished === false): ?>
    <div class="state notpublished"><?php echo $GLOBALS['TL_LANG']['ERR']['filenotpublished']; ?></div>
    <?php endif; ?>	
	<?php if($this->isPublished === true && $this->isFile === false): ?>
    <div class="state published filenotfound"><?php echo $GLOBALS['TL_LANG']['ERR']['filepublishedbutinexistend']; ?></div>
    <?php endif; ?>
</div>