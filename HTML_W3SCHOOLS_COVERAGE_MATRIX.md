# W3Schools HTML Element Coverage Matrix

**Generated**: November 6, 2025  
**Reference**: https://www.w3schools.com/html/  
**Status**: Phase 1 - Initial Analysis

## Legend
- ✅ **Implemented** - Class exists in src/Ksfraser/HTML/
- ⚠️ **Partial** - Basic class exists but may need enhancements
- ❌ **Missing** - Not implemented, needs creation
- 🔧 **Deprecated** - HTML element deprecated (e.g., `<font>`, `<center>`)
- N/A - Not applicable (meta elements, comments, etc.)

---

## Basic HTML Elements

| Element | Tag | Class Name | Status | Location | Notes |
|---------|-----|------------|--------|----------|-------|
| HTML | `<html>` | HtmlHtml | ✅ | src/Ksfraser/HTML/Elements/ | Root element |
| Head | `<head>` | HtmlHead | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Needs migration |
| Title | `<title>` | HtmlTitle | ✅ | src/Ksfraser/HTML/Elements/ | Document title |
| Body | `<body>` | HtmlBody | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Needs migration |
| Heading 1 | `<h1>` | HtmlHeading1 | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Needs migration |
| Heading 2 | `<h2>` | HtmlHeading2 | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Needs migration |
| Heading 3 | `<h3>` | HtmlHeading3 | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Needs migration |
| Heading 4 | `<h4>` | HtmlHeading4 | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Needs migration |
| Heading 5 | `<h5>` | HtmlHeading5 | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Needs migration |
| Heading 6 | `<h6>` | HtmlHeading6 | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Needs migration |
| Paragraph | `<p>` | HtmlP, HtmlParagraph | ✅ | views/HTML/ | Needs migration |
| Line Break | `<br>` | HtmlBr | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Empty element |
| Horizontal Rule | `<hr>` | HtmlHr | ✅ | views/HTML/ | Needs migration |
| Comment | `<!-- -->` | HtmlComment | ✅ | src/Ksfraser/HTML/Composites/ | Special handling |

**Summary**: 13/13 Basic elements implemented (100%)

---

## Formatting Elements

| Element | Tag | Class Name | Status | Location | Notes |
|---------|-----|------------|--------|----------|-------|
| Bold | `<b>` | HtmlB, HtmlBold | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Needs migration |
| Strong | `<strong>` | HtmlStrong | ✅ | views/HTML/ | Semantic bold |
| Italic | `<i>` | HtmlI, HtmlItalic | ✅ | views/HTML/ | Needs migration |
| Emphasized | `<em>` | HtmlEm, HtmlEmphasize | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Semantic italic |
| Mark | `<mark>` | HtmlMark | ✅ | views/HTML/ | Highlighted text |
| Small | `<small>` | HtmlSmall | ✅ | views/HTML/ | Smaller text |
| Deleted | `<del>` | HtmlDel, HtmlDeleted | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Strikethrough |
| Inserted | `<ins>` | HtmlIns, HtmlInserted | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Underline |
| Subscript | `<sub>` | HtmlSub, HtmlSubscript | ✅ | views/HTML/ | Subscript text |
| Superscript | `<sup>` | HtmlSup, HtmlSuperscript | ✅ | views/HTML/ | Superscript text |
| Code | `<code>` | HtmlCode | ❌ | N/A | **MISSING** |
| Keyboard | `<kbd>` | HtmlKbd | ❌ | N/A | **MISSING** |
| Sample | `<samp>` | HtmlSamp | ❌ | N/A | **MISSING** |
| Variable | `<var>` | HtmlVar | ❌ | N/A | **MISSING** |
| Preformatted | `<pre>` | HtmlPre, HtmlPreformatted | ✅ | views/HTML/ | Needs migration |
| Abbreviation | `<abbr>` | HtmlAbbr | ❌ | N/A | **MISSING** |
| Address | `<address>` | HtmlAddress | ❌ | N/A | **MISSING** |
| Blockquote | `<blockquote>` | HtmlBlockquote | ❌ | N/A | **MISSING** |
| Quote | `<q>` | HtmlQ | ❌ | N/A | **MISSING** |
| Cite | `<cite>` | HtmlCite | ❌ | N/A | **MISSING** |

**Summary**: 11/20 Formatting elements implemented (55%)  
**Missing**: code, kbd, samp, var, abbr, address, blockquote, q, cite

---

## Form Elements

| Element | Tag | Class Name | Status | Location | Notes |
|---------|-----|------------|--------|----------|-------|
| Form | `<form>` | HtmlForm | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Needs migration |
| Input | `<input>` | HtmlInput | ✅ | src/Ksfraser/HTML/Elements/ | Base input |
| Input Text | `<input type="text">` | HtmlInputText | ❌ | N/A | **MISSING** - Use HtmlInput |
| Input Password | `<input type="password">` | HtmlInputPassword | ❌ | N/A | **MISSING** |
| Input Radio | `<input type="radio">` | HtmlInputRadio | ❌ | N/A | **MISSING** |
| Input Checkbox | `<input type="checkbox">` | HtmlInputCheckbox | ❌ | N/A | **MISSING** |
| Input Submit | `<input type="submit">` | HtmlSubmit | ✅ | src/Ksfraser/HTML/Elements/ | Submit button |
| Input Button | `<input type="button">` | HtmlInputButton | ✅ | src/Ksfraser/HTML/Elements/ | Generic button |
| Input Reset | `<input type="reset">` | HtmlInputReset | ✅ | src/Ksfraser/HTML/Elements/ | Reset button |
| Input Hidden | `<input type="hidden">` | HtmlHidden | ✅ | src/Ksfraser/HTML/Elements/ | Hidden field |
| Input File | `<input type="file">` | HtmlInputFile | ❌ | N/A | **MISSING** |
| Input Email | `<input type="email">` | HtmlInputEmail | ❌ | N/A | **MISSING** |
| Input Number | `<input type="number">` | HtmlInputNumber | ❌ | N/A | **MISSING** |
| Input Date | `<input type="date">` | HtmlInputDate | ❌ | N/A | **MISSING** |
| Input Color | `<input type="color">` | HtmlInputColor | ❌ | N/A | **MISSING** |
| Input Range | `<input type="range">` | HtmlInputRange | ❌ | N/A | **MISSING** |
| Textarea | `<textarea>` | HtmlTextarea | ❌ | N/A | **MISSING** |
| Button | `<button>` | HtmlButton | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Needs migration |
| Select | `<select>` | HtmlSelect | ✅ | src/Ksfraser/HTML/Elements/ | Dropdown |
| Option | `<option>` | HtmlOption | ✅ | src/Ksfraser/HTML/Elements/ | Dropdown option |
| Optgroup | `<optgroup>` | HtmlOptgroup | ❌ | N/A | **MISSING** |
| Label | `<label>` | HtmlLabel | ❌ | N/A | **MISSING** |
| Fieldset | `<fieldset>` | HtmlFieldset | ❌ | N/A | **MISSING** |
| Legend | `<legend>` | HtmlLegend | ❌ | N/A | **MISSING** |
| Datalist | `<datalist>` | HtmlDatalist | ❌ | N/A | **MISSING** |
| Output | `<output>` | HtmlOutput | ❌ | N/A | **MISSING** |

**Summary**: 9/26 Form elements implemented (35%)  
**Missing**: Many input types, textarea, label, fieldset, legend, optgroup, datalist, output

---

## List Elements

| Element | Tag | Class Name | Status | Location | Notes |
|---------|-----|------------|--------|----------|-------|
| Unordered List | `<ul>` | HtmlUl, HtmlUnorderedList | ✅ | src/Ksfraser/HTML/Elements/ | Bullet list |
| Ordered List | `<ol>` | HtmlOl, HtmlOrderedList | ✅ | views/HTML/ | Numbered list |
| List Item | `<li>` | HtmlLi, HtmlListItem | ✅ | views/HTML/ | List item |
| Description List | `<dl>` | HtmlDl, HtmlDescriptionList | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Definition list |
| Description Term | `<dt>` | HtmlDt, HtmlDescriptionTerm | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Term |
| Description Definition | `<dd>` | HtmlDd, HtmlDescriptionDescription | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Definition |

**Summary**: 6/6 List elements implemented (100%)

---

## Table Elements

| Element | Tag | Class Name | Status | Location | Notes |
|---------|-----|------------|--------|----------|-------|
| Table | `<table>` | HtmlTable | ✅ | src/Ksfraser/HTML/Elements/ | Main table |
| Caption | `<caption>` | HtmlTableCaption | ✅ | views/HTML/ | Table caption |
| Table Head | `<thead>` | HtmlTableHead | ✅ | views/HTML/ | Header section |
| Table Body | `<tbody>` | HtmlTableBody | ✅ | views/HTML/ | Body section |
| Table Footer | `<tfoot>` | HtmlTableFoot | ✅ | views/HTML/ | Footer section |
| Table Row | `<tr>` | HtmlTableRow | ✅ | src/Ksfraser/HTML/Elements/ | Table row |
| Table Header Cell | `<th>` | HtmlTh, HtmlTableHeaderCell | ✅ | views/HTML/ | Header cell |
| Table Data Cell | `<td>` | HtmlTd, HtmlTableRowCell | ✅ | src/Ksfraser/HTML/Elements/ | Data cell |
| Col Group | `<colgroup>` | HtmlTableColGroup | ✅ | views/HTML/ | Column group |
| Col | `<col>` | HtmlTableCol | ✅ | views/HTML/ | Column |

**Summary**: 10/10 Table elements implemented (100%)

---

## Semantic HTML5 Elements

| Element | Tag | Class Name | Status | Location | Notes |
|---------|-----|------------|--------|----------|-------|
| Article | `<article>` | HtmlArticle | ❌ | N/A | **MISSING** |
| Section | `<section>` | HtmlSection | ❌ | N/A | **MISSING** |
| Nav | `<nav>` | HtmlNav | ❌ | N/A | **MISSING** |
| Aside | `<aside>` | HtmlAside | ❌ | N/A | **MISSING** |
| Header | `<header>` | HtmlHeader | ❌ | N/A | **MISSING** |
| Footer | `<footer>` | HtmlFooter | ❌ | N/A | **MISSING** |
| Main | `<main>` | HtmlMain | ❌ | N/A | **MISSING** |
| Figure | `<figure>` | HtmlFigure | ❌ | N/A | **MISSING** |
| Figcaption | `<figcaption>` | HtmlFigcaption | ❌ | N/A | **MISSING** |
| Details | `<details>` | HtmlDetails | ❌ | N/A | **MISSING** |
| Summary | `<summary>` | HtmlSummary | ❌ | N/A | **MISSING** |
| Dialog | `<dialog>` | HtmlDialog | ❌ | N/A | **MISSING** |
| Time | `<time>` | HtmlTime | ❌ | N/A | **MISSING** |

**Summary**: 0/13 Semantic elements implemented (0%)  
**Priority**: HIGH - These are modern HTML5 best practices

---

## Media Elements

| Element | Tag | Class Name | Status | Location | Notes |
|---------|-----|------------|--------|----------|-------|
| Image | `<img>` | HtmlImg, HtmlImage | ✅ | src/Ksfraser/HTML/Elements/ | Image (empty element) |
| Picture | `<picture>` | HtmlPicture | ❌ | N/A | **MISSING** |
| Audio | `<audio>` | HtmlAudio | ❌ | N/A | **MISSING** |
| Video | `<video>` | HtmlVideo | ❌ | N/A | **MISSING** |
| Source | `<source>` | HtmlSource | ❌ | N/A | **MISSING** |
| Track | `<track>` | HtmlTrack | ❌ | N/A | **MISSING** |
| Canvas | `<canvas>` | HtmlCanvas | ❌ | N/A | **MISSING** |
| SVG | `<svg>` | HtmlSvg | ❌ | N/A | **MISSING** |

**Summary**: 1/8 Media elements implemented (13%)  
**Priority**: MEDIUM - Useful for modern web apps

---

## Link and Script Elements

| Element | Tag | Class Name | Status | Location | Notes |
|---------|-----|------------|--------|----------|-------|
| Anchor | `<a>` | HtmlA, HtmlLink | ✅ | src/Ksfraser/HTML/Elements/ | Hyperlink |
| Link (CSS) | `<link>` | HtmlExternalCSS | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | External stylesheet |
| Style | `<style>` | HtmlStyle, HtmlInternalCSS | ✅ | views/HTML/ | Internal CSS |
| Script | `<script>` | HtmlScript | ❌ | N/A | **MISSING** |
| Noscript | `<noscript>` | HtmlNoscript | ❌ | N/A | **MISSING** |

**Summary**: 3/5 Link/Script elements implemented (60%)

---

## Container Elements

| Element | Tag | Class Name | Status | Location | Notes |
|---------|-----|------------|--------|----------|-------|
| Div | `<div>` | HtmlDiv | ✅ | src/Ksfraser/FaBankImport/views/HTML/ | Block container |
| Span | `<span>` | HtmlSpan | ✅ | views/HTML/ | Inline container |
| Iframe | `<iframe>` | HtmlIframe | ❌ | N/A | **MISSING** |

**Summary**: 2/3 Container elements implemented (67%)

---

## Meta Elements

| Element | Tag | Class Name | Status | Location | Notes |
|---------|-----|------------|--------|----------|-------|
| Meta | `<meta>` | HtmlMeta | ❌ | N/A | **MISSING** |
| Base | `<base>` | HtmlBase | ❌ | N/A | **MISSING** |

**Summary**: 0/2 Meta elements implemented (0%)

---

## Less Common Elements

| Element | Tag | Class Name | Status | Location | Notes |
|---------|-----|------------|--------|----------|-------|
| Progress | `<progress>` | HtmlProgress | ❌ | N/A | **MISSING** |
| Meter | `<meter>` | HtmlMeter | ❌ | N/A | **MISSING** |
| Map | `<map>` | HtmlMap | ❌ | N/A | **MISSING** |
| Area | `<area>` | HtmlArea | ❌ | N/A | **MISSING** |
| Embed | `<embed>` | HtmlEmbed | ❌ | N/A | **MISSING** |
| Object | `<object>` | HtmlObject | ❌ | N/A | **MISSING** |
| Param | `<param>` | HtmlParam | ❌ | N/A | **MISSING** |
| WBR | `<wbr>` | HtmlWbr | ❌ | N/A | **MISSING** |

**Summary**: 0/8 Less common elements implemented (0%)

---

## Overall Summary

| Category | Implemented | Total | Coverage |
|----------|-------------|-------|----------|
| Basic HTML | 13 | 13 | 100% ✅ |
| Formatting | 11 | 20 | 55% ⚠️ |
| Forms | 9 | 26 | 35% ⚠️ |
| Lists | 6 | 6 | 100% ✅ |
| Tables | 10 | 10 | 100% ✅ |
| Semantic HTML5 | 0 | 13 | 0% ❌ |
| Media | 1 | 8 | 13% ❌ |
| Links/Scripts | 3 | 5 | 60% ⚠️ |
| Containers | 2 | 3 | 67% ⚠️ |
| Meta | 0 | 2 | 0% ❌ |
| Less Common | 0 | 8 | 0% ❌ |
| **TOTAL** | **55** | **114** | **48%** |

---

## Priority Implementation List

### HIGH Priority (Essential for modern web apps)
1. **Semantic HTML5** (13 elements)
   - `<article>`, `<section>`, `<nav>`, `<aside>`, `<header>`, `<footer>`, `<main>`
   - `<figure>`, `<figcaption>`, `<details>`, `<summary>`, `<dialog>`, `<time>`

2. **Form Elements** (10 elements)
   - `<textarea>`, `<label>`, `<fieldset>`, `<legend>`
   - Input types: text, password, radio, checkbox, email, number, date, file

3. **Formatting** (9 elements)
   - `<code>`, `<kbd>`, `<samp>`, `<var>`
   - `<abbr>`, `<address>`, `<blockquote>`, `<q>`, `<cite>`

### MEDIUM Priority (Useful for enhanced functionality)
4. **Media Elements** (7 elements)
   - `<audio>`, `<video>`, `<source>`, `<track>`
   - `<picture>`, `<canvas>`, `<svg>`

5. **Script/Meta** (4 elements)
   - `<script>`, `<noscript>`, `<meta>`, `<base>`

### LOW Priority (Less common but complete)
6. **Less Common** (8 elements)
   - `<progress>`, `<meter>`, `<iframe>`
   - `<map>`, `<area>`, `<embed>`, `<object>`, `<param>`, `<wbr>`

---

## Migration Notes

**Files Needing Migration to src/Ksfraser/HTML/**:
- All files in `views/HTML/` (129 files)
- All files in `src/Ksfraser/FaBankImport/views/HTML/` (140 files)

**Namespace Updates Required**:
- Change from no namespace → `namespace Ksfraser\HTML\Elements;`
- Change from `namespace Ksfraser\FaBankImport\views\HTML;` → `namespace Ksfraser\HTML\Elements;`

**Test Coverage Target**: 80%+ for all classes

**PHPDoc Target**: 100% coverage with @param, @return, @throws, @example

---

## Next Actions

1. ✅ Complete this coverage matrix
2. ⏳ Begin directory consolidation (Phase 2)
3. ⏳ Implement HIGH priority missing elements using TDD
4. ⏳ Create unit tests for all existing classes
5. ⏳ Complete PHPDoc for all classes
6. ⏳ Create UML architecture documentation
7. ⏳ Prepare for git submodule extraction

**Estimated Effort**: 40-50 hours for complete consolidation and missing element implementation
