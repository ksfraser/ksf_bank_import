# Script to reorganize HTML library structure
# Moves element classes to Elements/ and composite classes to Composites/

$htmlDir = "src\Ksfraser\HTML"

# Composite classes (HTML_ROW*, HTML_TABLE*, composites)
$composites = @(
    "HTML_ROW.php",
    "HTML_ROW_LABEL.php",
    "HTML_ROW_LABELDecorator.php",
    "HTML_TABLE.php",
    "HtmlLabelRow.php",
    "LabelRowBase.php"
)

# Element classes (all Html* that extend HtmlElement or similar)
$elements = @(
    "HtmlA.php",
    "HtmlB.php",
    "HtmlBody.php",
    "HtmlBold.php",
    "HtmlBr.php",
    "HtmlButton.php",
    "HtmlComment.php",
    "HtmlDd.php",
    "HtmlDel.php",
    "HtmlDeleted.php",
    "HtmlDescriptionDescription.php",
    "HtmlDescriptionList.php",
    "HtmlDescriptionTerm.php",
    "HtmlDiv.php",
    "HtmlDl.php",
    "HtmlDt.php",
    "HtmlEm.php",
    "HtmlEmail.php",
    "HtmlEmphasize.php",
    "HtmlEmptyElement.php",
    "HtmlExternalCSS.php",
    "HtmlForm.php",
    "HtmlFormatting.php",
    "HtmlHead.php",
    "HtmlHeading.php",
    "HtmlHeading1.php",
    "HtmlHeading2.php",
    "HtmlHeading3.php",
    "HtmlHeading4.php",
    "HtmlHeading5.php",
    "HtmlHeading6.php",
    "HtmlHidden.php",
    "HtmlHr.php",
    "HtmlHtml.php",
    "HtmlI.php",
    "HtmlImage.php",
    "HtmlImg.php",
    "HtmlInput.php",
    "HtmlInputButton.php",
    "HtmlInputGenericButton.php",
    "HtmlInputReset.php",
    "HtmlIns.php",
    "HtmlInserted.php",
    "HtmlInternalCSS.php",
    "HtmlInternalCSSList.php",
    "HtmlInternalCSSListList.php",
    "HtmlItalic.php",
    "HtmlLi.php",
    "HtmlLink.php",
    "HtmlListItem.php",
    "HtmlMark.php",
    "HtmlOl.php",
    "HtmlOption.php",
    "HtmlOrderedList.php",
    "HtmlP.php",
    "HtmlParagraph.php",
    "HtmlPre.php",
    "HtmlPreformatted.php",
    "HtmlRaw.php",
    "HtmlSelect.php",
    "HtmlSmall.php",
    "HtmlSpan.php",
    "HtmlString.php",
    "HtmlStrong.php",
    "HtmlStyle.php",
    "HtmlStyleList.php",
    "HtmlSub.php",
    "HtmlSubmit.php",
    "HtmlSubscript.php",
    "HtmlSup.php",
    "HtmlSuperscript.php",
    "HtmlTable.php",
    "HtmlTableBody.php",
    "HtmlTableCaption.php",
    "HtmlTableCol.php",
    "HtmlTableColGroup.php",
    "HtmlTableFoot.php",
    "HtmlTableHead.php",
    "HtmlTableHeaderCell.php",
    "HtmlTableRow.php",
    "HtmlTableRowCell.php",
    "HtmlTd.php",
    "HtmlTh.php",
    "HtmlTitle.php",
    "HtmlUl.php",
    "HtmlUnorderedList.php"
)

Write-Host "Moving composite classes to Composites/..." -ForegroundColor Cyan
foreach ($file in $composites) {
    $source = Join-Path $htmlDir $file
    $dest = Join-Path $htmlDir "Composites\$file"
    if (Test-Path $source) {
        Move-Item $source $dest -Force
        Write-Host "  Moved: $file" -ForegroundColor Green
    } else {
        Write-Host "  Not found: $file" -ForegroundColor Yellow
    }
}

Write-Host "`nMoving element classes to Elements/..." -ForegroundColor Cyan
foreach ($file in $elements) {
    $source = Join-Path $htmlDir $file
    $dest = Join-Path $htmlDir "Elements\$file"
    if (Test-Path $source) {
        Move-Item $source $dest -Force
        Write-Host "  Moved: $file" -ForegroundColor Green
    } else {
        Write-Host "  Not found: $file" -ForegroundColor Yellow
    }
}

Write-Host "`nFiles remaining in root:" -ForegroundColor Cyan
Get-ChildItem "$htmlDir\*.php" | ForEach-Object {
    Write-Host "  $($_.Name)" -ForegroundColor White
}

Write-Host "`nDone! Now update namespaces and requires." -ForegroundColor Green
