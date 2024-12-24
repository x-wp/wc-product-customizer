<?php
/**
 * Customizer_Admin class file.
 *
 * @package eXtended WooCommerce
 * @subpackage Product Customizer
 */

namespace XWC\Product;

/**
 * Handles the custom product types and options in the admin.
 */
final class Customizer_Admin {
    /**
     * Product type options
     *
     * @var array
     */
    private array $types;

    /**
     * Product type options
     *
     * @var array<string, array{
     *   id: string,
     *   label: string,
     *   desc: string,
     *   for: array<string>,
     *   default?: bool|string,
     *   prop?: bool,
     * }>
     */
    private array $opts;

    /**
     * Undocumented variable
     *
     * @var array<string, array{
     *   class: array,
     *   label: string,
     *   icon: string,
     *   priority: int,
     *   target: string,
     *   id: string,
     *   panel: array<string>,
     * }>
     */
    private array $tabs;

    /**
     * Constructor
     *
     * @param  array $types Product types.
     * @param  array $opts  Product options.
     * @param  array $tabs  Product tabs.
     */
    public function __construct( array $types, array $opts, array $tabs ) {
        if ( ! \is_admin() ) {
            return;
        }

        $this->types = $types;
        $this->opts  = $opts;
        $this->tabs  = $tabs;

        \add_filter( 'product_type_selector', array( $this, 'add_types' ) );
        \add_filter( 'product_type_options', array( $this, 'add_options' ), 99, 1 );
        \add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_tabs' ), 999, 1 );
        \add_action( 'woocommerce_product_data_panels', array( $this, 'add_panels' ), 999 );
        \add_action( 'admin_print_styles', array( $this, 'add_css' ), 90 );
        \add_action( 'admin_footer', array( $this, 'add_js' ), 90, 1 );

        \add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_options' ) );
    }

    /**
     * Checks if we're on the product edit page
     *
     * @return bool
     */
    private function is_product_edit_page() {
        global $pagenow, $typenow;

        if ( ! \in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
            return false;
        }

        // phpcs:ignore WordPress.Security.NonceVerification
        $type = $typenow ?? \wc_clean( \wp_unslash( $_GET['post_type'] ?? '' ) );

        return 'product' === $type;
    }

    /**
     * Add product types to the product type selector.
     *
     * @param  array<string, string> $types Product types.
     * @return array<string, string>
     */
    public function add_types( array $types ): array {
        $new_types = \wp_filter_object_list( $this->types, array( 'name' => null ), 'not', 'name' );

        return \array_merge( $types, $new_types );
    }

    /**
     * Adds the custom product options checkboxes
     *
     * @param  array<string, array> $opts Product options.
     * @return array<string, array>
     */
    public function add_options( array $opts ): array {
        foreach ( $this->opts as $key => $opt ) {
            $opts[ $key ] = array(
                'default'       => \wc_bool_to_string( $opt['default'] ),
                'description'   => $opt['desc'],
                'id'            => '_' . $key,
                'label'         => $opt['label'],
                'wrapper_class' => \implode( ' ', $opt['for'] ),
            );
        }

        return $opts;
	}

    /**
     * Adds the custom product tabs
     *
     * @param  array<string, array> $tabs Product tabs.
     * @return array<string, array>
     */
    public function add_tabs( array $tabs ): array {
        foreach ( $this->tabs as $key => $tab ) {
            $tabs[ $key ] = \xwp_array_diff_assoc( $tab, 'icon', 'id' );
        }

        return $tabs;
    }

    /**
     * Add panels for the custom product tabs.
     */
    public function add_panels(): void {
        foreach ( $this->tabs as $key => $tab ) {
            $classes = array( 'panel' );
            $classes = \array_unique( \array_merge( $tab['panel'], $classes ) );
            $classes = \array_map( 'sanitize_html_class', $classes );

            \printf(
                '<div id="%1$s" class="%2$s" style="%3$s">',
                \esc_attr( $tab['target'] ),
                \esc_attr( \implode( ' ', $classes ) ),
                \esc_attr( 'display: none;' ),
            );

            \do_action( "xwc_product_options_{$key}" );

            echo '</div>';
        }
    }

    /**
     * Adds custom css needed for the custom product tab icons to work
     */
	public function add_css() {
        $tabs = \wp_filter_object_list( $this->tabs, array( 'icon' => '' ), 'not', 'icon' );

        if ( ! $tabs || ! $this->is_product_edit_page() ) {
            return;
        }

		$css = '';
		foreach ( $tabs as $key => $icon ) {
			$icon_font = \str_starts_with( $icon, 'woo' ) ? 'woocommerce' : 'Dashicons';
			$icon_str  = \str_replace( 'woo:', '', $icon );

			$css .= \sprintf(
                '#woocommerce-product-data ul.wc-tabs li.%1$s_options a::before { content: "%2$s"; font-family: %3$s, sans-serif; }%4$s',
                \esc_attr( $key ),
                \esc_attr( $icon_str ),
                \esc_attr( $icon_font ),
                "\n",
			);

		}

		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		\printf( '<styl%1$s type="text/css">%2$s</styl%1$s>', 'e', $css );
	}

    /**
     * Adds custom javascript needed for the custom product types to work
     */
	public function add_js() {
		if ( ! $this->is_product_edit_page() ) {
			return;
		}

		$opt_groups = array();

		$remap = static fn( array $arr, string $selector, string $action, string $type ) => \array_map(
            static fn( string $target ) => array(
				'class'  => "{$action}_if_{$type}",
				'target' => \sprintf( $selector, $target ),
            ),
            $arr,
        );

		foreach ( \array_merge( $this->opts, $this->types ) as $type => $data ) {
			$opt_groups = \array_merge(
                $opt_groups,
                $remap( $data['show_groups'] ?? array(), '.options_group.%s', 'show', $type ),
                $remap( $data['show_tabs'] ?? array(), '.%s_options', 'show', $type ),
                $remap( $data['extends'] ?? array(), '.show_if_%s', 'show', $type ),
                $remap( $data['extends'] ?? array(), '.hide_if_%s', 'hide', $type ),
			);
		}
		$opt_groups = \array_values( \array_filter( $opt_groups ) );

        if ( ! $opt_groups || ! \array_keys( $this->opts ) ) {
            return;
        }

		$script = <<<'JS'
            jQuery(($) => {
                const toggleVisibility = ($show, $hide, isChecked = true) => {
                    $show.toggle(isChecked);
                    $hide.toggle(!isChecked);
                };
                const getElements = (action, option) => {
                    return $(`.${action}_if_${option}`);
                };

                utilAdditionalTypes.forEach((optData) => {
                    $(optData.target).addClass(optData.class);
                });

                utilAdditionalOpts.forEach((opt) => {
                    const $checkbox = $(`input#_${opt}`);
                    const $showElements = getElements('show', opt);
                    const $hideElements = getElements('hide', opt);

                    $checkbox.on('change', (e) => toggleVisibility($showElements, $hideElements, $(e.target).prop('checked')));

                    toggleVisibility($showElements, $hideElements, $checkbox.prop('checked'));
                });

                $('select#product-type').change();
            });
        JS;

        //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		\printf(
            <<<'HTML'
                <!-- XWC_Customizer JS -->
                <script>
                    var utilAdditionalTypes = %s;
                    var utilAdditionalOpts = %s;
                    %s
                </script>
                HTML,
            \wp_json_encode( $opt_groups ),
            \wp_json_encode( \array_keys( $this->opts ) ),
            $script,
		);
        //phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

    /**
     * Save the custom product options.
     *
     * @param \WC_Product $product Product object.
     */
    public function save_options( \WC_Product $product ): void {
        foreach ( $this->opts as $key => [ 'prop' => $prop ] ) {
            $value = 'on' === \wc_get_post_data_by_key( "_{$key}", 'no' );
            $value = \wc_bool_to_string( $value );

            if ( $prop || \is_callable( array( $product, "set_{$key}" ) ) ) {
                $product->{"set_{$key}"}( $value );
                continue;
            }

            $product->update_meta_data( "_{$key}", $value );
		}
	}
}
