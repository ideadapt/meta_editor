-- Table `tl_metafile`
TRUNCATE `tl_metafile`;
INSERT INTO `tl_metafile` (
  `tstamp`,
  `title`,
  `folder`,
  `language`,
  `metatype`
) 
VALUES (
  UNIX_TIMESTAMP(),
  "gallery1",
  "tl_files/MetaEditorTest/nature/gallery1",
  "",
  '0'
);


-- Table `tl_metaitem`
TRUNCATE `tl_metaitem`;
INSERT INTO `tl_metaitem` (
  `pid`,
  `sorting`,
  `tstamp`,
  `filename`,
  `title`,
  `link`,
  `description`
) 
VALUES (
  1,
  1,
  UNIX_TIMESTAMP(),
  "tl_files/MetaEditorTest/nature/gallery1/image001.jpg",
  "image001.jpg",
  "",
  "I am image001.jpg"
),(
  1,
  2,
  UNIX_TIMESTAMP(),
  "tl_files/MetaEditorTest/nature/gallery1/image002.jpg",
  "image002.jpg",
  "",
  "I am image002.jpg"
),(
  1,
  4,
  UNIX_TIMESTAMP(),
  "tl_files/MetaEditorTest/nature/gallery1/image003.jpg",
  "image003.jpg",
  "",
  "I am image003.jpg"
),(
  1,
  8,
  UNIX_TIMESTAMP(),
  "tl_files/MetaEditorTest/nature/gallery1/image004.jpg",
  "image004.jpg",
  "",
  "I am image004.jpg"
),(
  1,
  16,
  UNIX_TIMESTAMP(),
  "tl_files/MetaEditorTest/nature/gallery1/image005.jpg",
  "image005.jpg",
  "",
  "I am image005.jpg"
);