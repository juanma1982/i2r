<?php

use Goteo\Core\View,
    Goteo\Library\Text,
    Goteo\Library\SuperForm;

$contract = $this['contract'];
$types   = $this['types'];
$errors = $contract->errors ?: array();

// miramos el pruimer paso con errores para mandarlo a ese
$goto = 'view-step-promoter';
foreach ($this['steps'] as $id => $data) {

    if (empty($step) && !empty($contract->errors[$id])) {
        $goto = 'view-step-' . $id;
        break;
    }
}

// boton de revisar que no sirve para mucho
$buttons = array(
    'review' => array(
        'type'  => 'submit',
        'name'  => $goto,
        'label' => Text::get('form-self_review-button'),
        'class' => 'retry'
    )
);

// si es enviable ponemos el boton
if ($contract->finishable) {
    $buttons['finish'] = array(
        'type'  => 'submit',
        'name'  => 'finish',
        'label' => 'Cerrar datos',
        'class' => 'confirm red'
    );
} else {
    $buttons['nofinish'] = array(
        'type'  => 'submit',
        'name'  => 'nofinish',
        'label' => 'Cerrar datos',
        'class' => 'confirm disabled',
        'disabled' => 'disabled'
    );
}

// elementos generales de final
$elements      = array(
    'process_final' => array (
        'type' => 'hidden',
        'value' => 'final'
    ),

    'final' => array(
        'type'      => 'html',
        'class'     => 'fullwidth',
        'html'      =>   '<div class="contract-final" style="position: relative"><div>'
                       . '<div class="overlay" style="position: absolute; left: 0; top: 0; right: 0; bottom: 0; z-index: 999"></div>'
                       . '<div style="z-index: 0">'
                       . new View('view/contract/widget/review.html.php', array('contract' => $contract))
                       . '</div>'
                       . '</div></div>'
    )
);

// Footer
$elements['footer'] = array(
    'type'      => 'group',
    'children'  => array(
        'errors' => array(
            'title' => Text::get('form-footer-errors_title'),
            'view'  => new View('view/contract/edit/errors.html.php', array(
                'contract'   => $contract,
                'step'      => $this['step']
            ))                    
        ),
        'buttons'  => array(
            'type'  => 'group',
            'children' => $buttons
        )
    )

);

// lanzamos el superform
echo new SuperForm(array(
    'action'        => '',
    'level'         => $this['level'],
    'method'        => 'post',
    'title'         => Text::get('final-main-header'),
    'hint'          => Text::get('guide-contract-final'),
    'elements'      => $elements
));