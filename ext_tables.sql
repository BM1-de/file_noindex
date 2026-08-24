#
# Table structure for table 'sys_file_metadata'
#
# Only needed for TYPO3 v12: since v13 the core derives the column from the TCA
# definition. The type matches what DefaultTcaSchema generates for a TCA field
# of type "check" (smallint, unsigned, not null, default 0), so installations on
# v13/v14 do not see a schema change.
#
CREATE TABLE sys_file_metadata (
	tx_filenoindex_noindex smallint(5) unsigned DEFAULT '0' NOT NULL
);
