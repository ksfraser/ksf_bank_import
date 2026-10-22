<?php

declare(strict_types=1);

/**
 * Test doubles for the two classes the legacy BiLineItemController depends
 * on. They cannot be autoloaded, therefore the doubles are declared with the
 * exact class names the controller type hints against.
 *
 * Why the production classes are not used:
 *
 *  - Ksfraser\FaBankImport\Model\BiLineItemModel lives in
 *    src/Ksfraser/FaBankImport/model/BiLineItemModel.php (lower case
 *    directory) while composer maps the namespace to .../Model/ - on a case
 *    sensitive file system the class is not resolvable. Its parent class
 *    Ksfraser\common\GenericFaInterface does not exist in this repository
 *    either, so the file could not be loaded even by hand.
 *  - Ksfraser\FaBankImport\View\BiLineItemView lives in
 *    src/Ksfraser/FaBankImport/view/BiLineItemView.php (lower case
 *    directory) with the same problem.
 *
 * These are test doubles, not production code.
 */

namespace Ksfraser\FaBankImport\Model {

    if (!class_exists('Ksfraser\FaBankImport\Model\BiLineItemModel', false)) {
        /**
         * Stand-in for the (unloadable) production line item model.
         */
        class BiLineItemModel
        {
            /** @var array */
            public $data = [];

            /**
             * @param array $data Line item data.
             */
            public function __construct(array $data = [])
            {
                $this->data = $data;
            }
        }
    }
}

namespace Ksfraser\FaBankImport\View {

    if (!class_exists('Ksfraser\FaBankImport\View\BiLineItemView', false)) {
        /**
         * Stand-in for the (unloadable) production line item view.
         */
        class BiLineItemView
        {
            /** @var array */
            public $rendered = [];

            /**
             * @param object $model Model that is rendered.
             * @return void
             */
            public function displayLeft($model): void
            {
                $this->rendered[] = ['left', $model];
            }

            /**
             * @param object $model Model that is rendered.
             * @return void
             */
            public function displayRight($model): void
            {
                $this->rendered[] = ['right', $model];
            }
        }
    }
}
