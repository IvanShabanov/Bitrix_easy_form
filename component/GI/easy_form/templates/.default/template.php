<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

echo '<div id="FRONTEND_FORM_'.$arResult['FORM_ID'].'" class="FRONTEND_FORM '.$arResult['CLASS'].'">';

if ($arResult['SUCCESS'] == 'Y') {
    echo '<p class="succes_text">'.$arResult['SUCCESS_TEXT'].'</p>';
} else {
    if (is_array($arResult['ERRORS'])) {
        foreach ($arResult['ERRORS'] as $error) {
            echo '<p class="error">'.$error.'</p>';
        };
    };
    echo  $arResult['FORM_START'];
    echo  '<p class="title">'.$arResult['TITLE'].'</p>';
    echo  '<p class="description">'.$arResult['DESCRIPTION'].'</p>';
    foreach ($arResult['FIELDS'] as $field) {
        echo $field['html'];
    }
    echo  $arResult['REQURED_TEXT_WARNING'];
    echo  '<p class="description_after">'.$arResult['DESCRIPTION_AFTER'].'</p>';
    echo  $arResult['FORM_END'];
}
echo '</div>'
?>