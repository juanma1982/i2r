<?php

namespace Goteo\Controller {

    use Goteo\Core\ACL,
        Goteo\Core\Error,
        Goteo\Core\Redirection,
        Goteo\Core\View,
        Goteo\Library\Text,
        Goteo\Library\Mail,
        Goteo\Library\Template,
        Goteo\Library\Message,
        Goteo\Library\Feed,
        Goteo\Model;

//@TODO: ACL, cerrado para todos y se abre al impulsor 
    //      o bien, abierto y se verifica por código
    
    
    
    class Contract extends \Goteo\Core\Controller {

        /**
         * La vista por defecto del contrato ES el pdf
         * 
         * @param string(50) $id del proyecto
         * @return \Goteo\Core\View   Pdf
         */
        public function index($id = null) {
            
            $contract = Model\Contract::get($id); // datos del contrato
            $project  = Model\Project::get($id, null); // datos del proyecto

            // solamente se puede ver si....
            // Es un admin, es el impulsor
            // 
            $grant = false;
            if ($contract->owner == $_SESSION['user']->id)  // es el dueño del proyecto
                $grant = true;
            elseif (ACL::check('/contract/edit/'.$id))  // puede editar el proyecto
                $grant = true;
            elseif (ACL::check('/contract/edit/todos'))  // es un admin
                $grant = true;

            // si lo puede ver
            if ($grant) {
                $viewData = array(
                        'contract' => $contract,
                        'project' => $project
                    );

                // si existe el archivo físico lo mostramos
                // si no existe, lo generamos con los datos actuales
                if (!empty($contract->status->pdf)) {
                    $viewData['pdf'] = ''; // coger el get contents del archivo y sacarlo talcual
                } else {
                    // montar el contenido del pdf con los datops del contrato
                }

                return new View('view/contract/view.html.php', $viewData);
            } else {
                // no lo puede ver y punto
                throw new Redirection("/");
            }
        }

        public function raw ($id) {
            $contract = Model\Contract::get($id, LANG);
            \trace($contract);
            die;
        }

        // los contratos no se pueden eliminar... ¿o sí?
        public function delete ($id) {
            /*
            $contract = Model\Contract::get($id);
            $errors = array();
            if ($contract->delete($errors)) {
                Message::Info("Has borrado los datos del proyecto '<strong>{$contract->name}</strong>' correctamente");
                if ($_SESSION['contract']->id == $id) {
                    unset($_SESSION['contract']);
                }
            } else {
                Message::Info("No se han podido borrar los datos del proyecto '<strong>{$contract->name}</strong>'. Error:" . implode(', ', $errors));
            }
             */
            throw new Redirection("/dashboard/projects/contract");
        }

        //Aunque no esté en estado edición un admin siempre podrá editar los datos de contrato
        public function edit ($id) {
            $contract = Model\Contract::get($id, null);

            // aunque pueda acceder edit, no lo puede editar si los datos ya se han dado por cerrados
            if ($contract->owner != $_SESSION['user']->id // no es su proyecto
                && $contract->status->owner
                && !isset($_SESSION['user']->roles['gestor']) // no es un gestor
                && !isset($_SESSION['user']->roles['superadmin']) // no es superadmin
                ) {
                // le mostramos el pdf
                throw new Redirection('/contract/'.$id);
            }

            // todos los pasos, entrando en datos del promotor por defecto
            $step = 'promoter';

            $steps = array(
                'promoter' => array(
                    'name' => 'Promotor',
                    'title' => 'Promotor',
                    'class' => 'first-on on',
                    'num' => '1'
                ),
                'accounts' => array(
                    'name' => 'Cuentas',
                    'title' => 'Cuentas',
                    'class' => 'on-on on',
                    'num' => '2'
                ),
                'additional' => array(
                    'name' => 'Detalles',
                    'title' => 'Detalles',
                    'class' => 'on-off on',
                    'num' => '3'
                ),
                'final' => array(
                    'name' => 'Revisión',
                    'title' => 'Revisión',
                    'class' => 'off-last off',
                    'num' => '4'
                )
            );
            
                        
            
            foreach ($_REQUEST as $k => $v) {                
                if (strncmp($k, 'view-step-', 10) === 0 && !empty($v) && !empty($steps[substr($k, 10)])) {
                    $step = substr($k, 10);
                }                
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $errors = array(); // errores al procesar, no son errores en los datos del proyecto
                foreach ($steps as $id => &$data) {
                    
                    if (call_user_func_array(array($this, "process_{$id}"), array(&$contract, &$errors))) {
                        // ok
                    }
                    
                }

                // guardamos los datos que hemos tratado y los errores de los datos
                $contract->save($errors);
            }

            // variables para la vista
            $viewData = array(
                'contract' => $contract,
                'steps' => $steps,
                'step' => $step
            );


            // segun el paso añadimos los datos auxiliares para pintar
            switch ($step) {
                // datos del promotor
                case 'promoter': // cambiar luego a promoter
                    // si no tiene registro de contrato, cargamos los datos personales del usuario
                    // si tiene registro de contrato, cargamos de ahí
                    break;
                
                // cuentas bancarias
                case 'accounts':
                    // cargamos los datos de las cuentas del proyecto (ver dashboard)
//                    $viewData['accounts'] = Model\Contract::currentStatus();
                    break;

                // datos adicionales
                case 'additionals':
                    break;

                // revisión final
                case 'final':
                    break;

            }

            $view = new View (
                "view/contract/edit.html.php",
                $viewData
            );

            return $view;

        }

        /*
         * Promotor
         */
        private function process_promoter(&$contract, &$errors) {
            if (!isset($_POST['process_promoter'])) {
                return false;
            }

            // campos que guarda este paso. Verificar luego. 
            $fields = array(
                'type',
                'name',
                'nif',
                'office',
                'address',
                'location',
                'region',
                'zipcode',
                'country',
                'entity_name',
                'entity_cif',
                'entity_address',
                'entity_location',
                'entity_region',
                'entity_zipcode',
                'entity_country'
            );

            foreach ($fields as $field) {
                $contract->$field = $_POST[$field];
            }

            return true;
        }

        /*
         * Cuentas
         * Dualidad: En 
         * 
         * 
         */
        private function process_accounts(&$contract, &$errors) {
            if (!isset($_POST['process_accounts'])) {
                return false;
            }

            // también en la tabla de cuentas
            $accounts = Model\Project\Account::get($contract->project);
            
            $fields = array(
                'bank',
                'bank_owner',
                'paypal',
                'paypal_owner'
            );

            foreach ($fields as $field) {
                $contract->$field = $_POST[$field];
                $accounts->$field = $_POST[$field];
            }
            
            $accounts->save($errors);
            
            return true;
        }

        /*
         * Datos adicionales, verificar luego
         * Descripción del proyecto (para contrato)
         * datos de registro,
         * 
         */

        private function process_additionals(&$contract, &$errors) {
            if (!isset($_POST['process_additionals'])) {
                return false;
            }

            $fields = array(
                'birthdate',
                'project_description',
                'reg_name',
                'reg_number',
                'reg_id'
            );

            foreach ($fields as $field) {
                $contract->$field = $_POST[$field];
            }
            
            return true;
        }

        /*
         * Paso final, revisión y cierre
         */
        private function process_final(&$contract, &$errors) {
            if (!isset($_POST['process_final'])) {
                return false;
            }

            // este paso solo cambia el campo de cerrado (y flag de cerrado por impulsor)
            
            return true;
        }

        //-------------------------------------------------------------
        // Hasta aquí los métodos privados para el tratamiento de datos
        //-------------------------------------------------------------
   }

}