<?php
/**
 * HTML Library File Migration Map
 * 
 * Maps current files to their new locations and namespaces
 * 
 * Format:
 * 'CurrentFile.php' => [
 *     'new_path' => 'Elements/Category/CurrentFile.php',
 *     'new_namespace' => 'Ksfraser\HTML\Elements\Category',
 *     'category' => 'elemental|composite|base'
 * ]
 */

return [
    // BASE CLASSES (Stay in root)
    'HtmlElement.php' => ['new_path' => 'HtmlElement.php', 'new_namespace' => 'Ksfraser\HTML', 'category' => 'base'],
    'HtmlElementInterface.php' => ['new_path' => 'HtmlElementInterface.php', 'new_namespace' => 'Ksfraser\HTML', 'category' => 'base'],
    'HtmlEmptyElement.php' => ['new_path' => 'HtmlEmptyElement.php', 'new_namespace' => 'Ksfraser\HTML', 'category' => 'base'],
    'HtmlAttribute.php' => ['new_path' => 'HtmlAttribute.php', 'new_namespace' => 'Ksfraser\HTML', 'category' => 'base'],
    'HtmlAttributeList.php' => ['new_path' => 'HtmlAttributeList.php', 'new_namespace' => 'Ksfraser\HTML', 'category' => 'base'],
    'HtmlAttributesTrait.php' => ['new_path' => 'HtmlAttributesTrait.php', 'new_namespace' => 'Ksfraser\HTML', 'category' => 'base'],
    'HTMLChildrenTrait.php' => ['new_path' => 'HTMLChildrenTrait.php', 'new_namespace' => 'Ksfraser\HTML', 'category' => 'base'],
    'HtmlFormatting.php' => ['new_path' => 'HtmlFormatting.php', 'new_namespace' => 'Ksfraser\HTML', 'category' => 'base'],
    'HtmlString.php' => ['new_path' => 'HtmlString.php', 'new_namespace' => 'Ksfraser\HTML', 'category' => 'base'],
    'HtmlRaw.php' => ['new_path' => 'HtmlRaw.php', 'new_namespace' => 'Ksfraser\HTML', 'category' => 'base'],
    'HtmlComment.php' => ['new_path' => 'HtmlComment.php', 'new_namespace' => 'Ksfraser\HTML', 'category' => 'base'],
    
    // COMPOSITES (Move to Composites/)
    'HtmlLabelRow.php' => ['new_path' => 'Composites/HtmlLabelRow.php', 'new_namespace' => 'Ksfraser\HTML\Composites', 'category' => 'composite'],
    'HTML_ROW_LABEL.php' => ['new_path' => 'Composites/HTML_ROW_LABEL.php', 'new_namespace' => 'Ksfraser\HTML\Composites', 'category' => 'composite'],
    'HTML_ROW.php' => ['new_path' => 'Composites/HTML_ROW.php', 'new_namespace' => 'Ksfraser\HTML\Composites', 'category' => 'composite'],
    'HTML_ROW_LABELDecorator.php' => ['new_path' => 'Composites/HTML_ROW_LABELDecorator.php', 'new_namespace' => 'Ksfraser\HTML\Composites', 'category' => 'composite'],
    'HTML_TABLE.php' => ['new_path' => 'Composites/HTML_TABLE.php', 'new_namespace' => 'Ksfraser\HTML\Composites', 'category' => 'composite'],
    'LabelRowBase.php' => ['new_path' => 'Composites/LabelRowBase.php', 'new_namespace' => 'Ksfraser\HTML\Composites', 'category' => 'composite'],
    'FaUiFunctions.php' => ['new_path' => 'Composites/FaUiFunctions.php', 'new_namespace' => 'Ksfraser\HTML\Composites', 'category' => 'composite'],
    
    // TEXT ELEMENTS
    'HtmlB.php' => ['new_path' => 'Elements/Text/HtmlB.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Text', 'category' => 'element'],
    'HtmlBold.php' => ['new_path' => 'Elements/Text/HtmlBold.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Text', 'category' => 'element'],
    'HtmlI.php' => ['new_path' => 'Elements/Text/HtmlI.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Text', 'category' => 'element'],
    'HtmlItalic.php' => ['new_path' => 'Elements/Text/HtmlItalic.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Text', 'category' => 'element'],
    'HtmlEm.php' => ['new_path' => 'Elements/Text/HtmlEm.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Text', 'category' => 'element'],
    'HtmlEmphasize.php' => ['new_path' => 'Elements/Text/HtmlEmphasize.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Text', 'category' => 'element'],
    'HtmlStrong.php' => ['new_path' => 'Elements/Text/HtmlStrong.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Text', 'category' => 'element'],
    'HtmlSmall.php' => ['new_path' => 'Elements/Text/HtmlSmall.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Text', 'category' => 'element'],
    'HtmlMark.php' => ['new_path' => 'Elements/Text/HtmlMark.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Text', 'category' => 'element'],
    'HtmlDel.php' => ['new_path' => 'Elements/Text/HtmlDel.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Text', 'category' => 'element'],
    'HtmlDeleted.php' => ['new_path' => 'Elements/Text/HtmlDeleted.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Text', 'category' => 'element'],
    'HtmlIns.php' => ['new_path' => 'Elements/Text/HtmlIns.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Text', 'category' => 'element'],
    'HtmlInserted.php' => ['new_path' => 'Elements/Text/HtmlInserted.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Text', 'category' => 'element'],
    'HtmlSub.php' => ['new_path' => 'Elements/Text/HtmlSub.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Text', 'category' => 'element'],
    'HtmlSubscript.php' => ['new_path' => 'Elements/Text/HtmlSubscript.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Text', 'category' => 'element'],
    'HtmlSup.php' => ['new_path' => 'Elements/Text/HtmlSup.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Text', 'category' => 'element'],
    'HtmlSuperscript.php' => ['new_path' => 'Elements/Text/HtmlSuperscript.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Text', 'category' => 'element'],
    
    // STRUCTURE ELEMENTS
    'HtmlDiv.php' => ['new_path' => 'Elements/Structure/HtmlDiv.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Structure', 'category' => 'element'],
    'HtmlSpan.php' => ['new_path' => 'Elements/Structure/HtmlSpan.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Structure', 'category' => 'element'],
    'HtmlP.php' => ['new_path' => 'Elements/Structure/HtmlP.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Structure', 'category' => 'element'],
    'HtmlParagraph.php' => ['new_path' => 'Elements/Structure/HtmlParagraph.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Structure', 'category' => 'element'],
    'HtmlBr.php' => ['new_path' => 'Elements/Structure/HtmlBr.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Structure', 'category' => 'element'],
    'HtmlHr.php' => ['new_path' => 'Elements/Structure/HtmlHr.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Structure', 'category' => 'element'],
    'HtmlPre.php' => ['new_path' => 'Elements/Structure/HtmlPre.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Structure', 'category' => 'element'],
    'HtmlPreformatted.php' => ['new_path' => 'Elements/Structure/HtmlPreformatted.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Structure', 'category' => 'element'],
    
    // HEADINGS
    'HtmlHeading.php' => ['new_path' => 'Elements/Headings/HtmlHeading.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Headings', 'category' => 'element'],
    'HtmlHeading1.php' => ['new_path' => 'Elements/Headings/HtmlHeading1.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Headings', 'category' => 'element'],
    'HtmlHeading2.php' => ['new_path' => 'Elements/Headings/HtmlHeading2.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Headings', 'category' => 'element'],
    'HtmlHeading3.php' => ['new_path' => 'Elements/Headings/HtmlHeading3.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Headings', 'category' => 'element'],
    'HtmlHeading4.php' => ['new_path' => 'Elements/Headings/HtmlHeading4.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Headings', 'category' => 'element'],
    'HtmlHeading5.php' => ['new_path' => 'Elements/Headings/HtmlHeading5.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Headings', 'category' => 'element'],
    'HtmlHeading6.php' => ['new_path' => 'Elements/Headings/HtmlHeading6.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Headings', 'category' => 'element'],
    
    // LINKS
    'HtmlA.php' => ['new_path' => 'Elements/Links/HtmlA.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Links', 'category' => 'element'],
    'HtmlLink.php' => ['new_path' => 'Elements/Links/HtmlLink.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Links', 'category' => 'element'],
    'HtmlEmail.php' => ['new_path' => 'Elements/Links/HtmlEmail.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Links', 'category' => 'element'],
    
    // LISTS
    'HtmlUl.php' => ['new_path' => 'Elements/Lists/HtmlUl.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Lists', 'category' => 'element'],
    'HtmlUnorderedList.php' => ['new_path' => 'Elements/Lists/HtmlUnorderedList.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Lists', 'category' => 'element'],
    'HtmlOl.php' => ['new_path' => 'Elements/Lists/HtmlOl.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Lists', 'category' => 'element'],
    'HtmlOrderedList.php' => ['new_path' => 'Elements/Lists/HtmlOrderedList.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Lists', 'category' => 'element'],
    'HtmlLi.php' => ['new_path' => 'Elements/Lists/HtmlLi.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Lists', 'category' => 'element'],
    'HtmlListItem.php' => ['new_path' => 'Elements/Lists/HtmlListItem.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Lists', 'category' => 'element'],
    'HtmlDl.php' => ['new_path' => 'Elements/Lists/HtmlDl.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Lists', 'category' => 'element'],
    'HtmlDescriptionList.php' => ['new_path' => 'Elements/Lists/HtmlDescriptionList.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Lists', 'category' => 'element'],
    'HtmlDt.php' => ['new_path' => 'Elements/Lists/HtmlDt.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Lists', 'category' => 'element'],
    'HtmlDescriptionTerm.php' => ['new_path' => 'Elements/Lists/HtmlDescriptionTerm.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Lists', 'category' => 'element'],
    'HtmlDd.php' => ['new_path' => 'Elements/Lists/HtmlDd.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Lists', 'category' => 'element'],
    'HtmlDescriptionDescription.php' => ['new_path' => 'Elements/Lists/HtmlDescriptionDescription.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Lists', 'category' => 'element'],
    
    // TABLES
    'HtmlTable.php' => ['new_path' => 'Elements/Tables/HtmlTable.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Tables', 'category' => 'element'],
    'HtmlTableRow.php' => ['new_path' => 'Elements/Tables/HtmlTableRow.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Tables', 'category' => 'element'],
    'HtmlTd.php' => ['new_path' => 'Elements/Tables/HtmlTd.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Tables', 'category' => 'element'],
    'HtmlTableRowCell.php' => ['new_path' => 'Elements/Tables/HtmlTableRowCell.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Tables', 'category' => 'element'],
    'HtmlTh.php' => ['new_path' => 'Elements/Tables/HtmlTh.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Tables', 'category' => 'element'],
    'HtmlTableHeaderCell.php' => ['new_path' => 'Elements/Tables/HtmlTableHeaderCell.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Tables', 'category' => 'element'],
    'HtmlTableHead.php' => ['new_path' => 'Elements/Tables/HtmlTableHead.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Tables', 'category' => 'element'],
    'HtmlTableBody.php' => ['new_path' => 'Elements/Tables/HtmlTableBody.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Tables', 'category' => 'element'],
    'HtmlTableFoot.php' => ['new_path' => 'Elements/Tables/HtmlTableFoot.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Tables', 'category' => 'element'],
    'HtmlTableCaption.php' => ['new_path' => 'Elements/Tables/HtmlTableCaption.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Tables', 'category' => 'element'],
    'HtmlTableCol.php' => ['new_path' => 'Elements/Tables/HtmlTableCol.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Tables', 'category' => 'element'],
    'HtmlTableColGroup.php' => ['new_path' => 'Elements/Tables/HtmlTableColGroup.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Tables', 'category' => 'element'],
    
    // FORMS
    'HtmlForm.php' => ['new_path' => 'Elements/Forms/HtmlForm.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Forms', 'category' => 'element'],
    'HtmlInput.php' => ['new_path' => 'Elements/Forms/HtmlInput.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Forms', 'category' => 'element'],
    'HtmlInputButton.php' => ['new_path' => 'Elements/Forms/HtmlInputButton.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Forms', 'category' => 'element'],
    'HtmlInputGenericButton.php' => ['new_path' => 'Elements/Forms/HtmlInputGenericButton.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Forms', 'category' => 'element'],
    'HtmlInputReset.php' => ['new_path' => 'Elements/Forms/HtmlInputReset.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Forms', 'category' => 'element'],
    'HtmlSubmit.php' => ['new_path' => 'Elements/Forms/HtmlSubmit.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Forms', 'category' => 'element'],
    'HtmlButton.php' => ['new_path' => 'Elements/Forms/HtmlButton.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Forms', 'category' => 'element'],
    'HtmlHidden.php' => ['new_path' => 'Elements/Forms/HtmlHidden.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Forms', 'category' => 'element'],
    'HtmlSelect.php' => ['new_path' => 'Elements/Forms/HtmlSelect.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Forms', 'category' => 'element'],
    'HtmlOption.php' => ['new_path' => 'Elements/Forms/HtmlOption.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Forms', 'category' => 'element'],
    
    // DOCUMENT
    'HtmlHtml.php' => ['new_path' => 'Elements/Document/HtmlHtml.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Document', 'category' => 'element'],
    'HtmlHead.php' => ['new_path' => 'Elements/Document/HtmlHead.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Document', 'category' => 'element'],
    'HtmlBody.php' => ['new_path' => 'Elements/Document/HtmlBody.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Document', 'category' => 'element'],
    'HtmlTitle.php' => ['new_path' => 'Elements/Document/HtmlTitle.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Document', 'category' => 'element'],
    
    // MEDIA
    'HtmlImg.php' => ['new_path' => 'Elements/Media/HtmlImg.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Media', 'category' => 'element'],
    'HtmlImage.php' => ['new_path' => 'Elements/Media/HtmlImage.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Media', 'category' => 'element'],
    
    // OTHER
    'HtmlStyle.php' => ['new_path' => 'Elements/Other/HtmlStyle.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Other', 'category' => 'element'],
    'HtmlStyleList.php' => ['new_path' => 'Elements/Other/HtmlStyleList.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Other', 'category' => 'element'],
    'HtmlExternalCSS.php' => ['new_path' => 'Elements/Other/HtmlExternalCSS.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Other', 'category' => 'element'],
    'HtmlInternalCSS.php' => ['new_path' => 'Elements/Other/HtmlInternalCSS.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Other', 'category' => 'element'],
    'HtmlInternalCSSList.php' => ['new_path' => 'Elements/Other/HtmlInternalCSSList.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Other', 'category' => 'element'],
    'HtmlInternalCSSListList.php' => ['new_path' => 'Elements/Other/HtmlInternalCSSListList.php', 'new_namespace' => 'Ksfraser\HTML\Elements\Other', 'category' => 'element'],
];
