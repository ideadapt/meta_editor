<div class="cte_type"><strong><?php echo $this->title; ?></strong></div>
<div class="limit_height h52 block metaitem">
	<?php
	if($this->isFile):
		if($this->isImage):
	?>
		<div class="image_container"><img src="<?php echo $this->imgPath;?>" alt="<?php echo $this->title;?>" /></div>
	<?php
		else:
	?>
		<a href="<?php echo $this->filename ?>" class="download" style="background-image: url(<?php echo $this->iconFilename; ?>)"><?php echo $this->downloadText; ?></a><br />
	<?php
		endif;
	else:
		echo $this->errNoFile;
	endif;
	echo $this->description;
?>
</div>