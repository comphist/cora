/* Class: SecondaryCommentTagset

   Class representing secondary comments.
 */
var SecondaryCommentTagset = new Class({
    Extends: CommentTagset,
    classname: 'Sekundär-Kommentar',
    eventString: 'input:relay(input.et-input-sec_comment)',
    inputElement: '.editTable_sec_comment input',
    dataKey: 'anno_sec_comment'
});
