<?php //phpcs:disable Squiz.Commenting.FunctionComment.MissingParamName, Squiz.Commenting.FunctionComment.MissingParamTag
/**
 * Product_Type_Extender class file.
 *
 * @package eXtended WooCommerce
 * @subpackage Product Customizer
 */

namespace XWC\Product;

/**
 * Enables easy extension of product types.
 */
abstract class Customizer_Base {
    /**
     * Default product types
     *
     * @var array<string>
     */
    protected const DEFAULT_TYPES = array( 'simple', 'grouped', 'external', 'variable', 'variation' );

    /**
     * Default product options
     *
     * @var array<string>
     */
    protected const DEFAULT_OPTS = array( 'virtual', 'downloadable' );

    /**
     * Customizer admin instance
     *
     * @var Customizer_Admin|null
     */
    protected static ?Customizer_Admin $admin = null;

    /**
     * Product types array
     *
     * @var array
     */
    protected static array $types;

    /**
     * Product classnames array
     *
     * @var array
     */
    protected static array $cnames;

    /**
     * Product options array
     *
     * @var array
     */
    protected static array $opts;

    /**
     * Product tabs array
     *
     * @var array
     */
    protected static array $tabs;

    /**
     * Whether the class has been initialized
     *
     * @var bool
     */
    protected static bool $init;

    /**
     * Class constructor
     */
    public function __construct() {
        \add_filter( 'xwc_product_types', array( $this, 'custom_product_types' ), 100, 1 );
        \add_filter( 'xwc_product_opts', array( $this, 'custom_product_opts' ), 100, 1 );
        \add_filter( 'xwc_product_tabs', array( $this, 'custom_product_tabs' ), 100, 1 );

        static::$init ??= $this->init();
    }

    /**
     * Checks if the given type is a default type
     *
     * @param  string $type Product type.
     * @return bool
     */
    final protected function is_default_type( string $type ): bool {
        return \in_array( $type, static::DEFAULT_TYPES, true );
    }

    /**
     * Checks if the given option is a default option
     *
     * @param  string $opt Product option.
     * @return bool
     */
    final protected function is_default_opt( string $opt ): bool {
        return \in_array( $opt, static::DEFAULT_OPTS, true );
    }

    /**
     * Initializes the customizer framework
     *
     * @return true
     */
    protected function init(): bool {
        \add_action( 'before_woocommerce_init', array( $this, 'run_framework' ), 100000, 0 );
        \add_filter( 'woocommerce_product_class', array( $this, 'modify_classname' ), 99, 2 );

        return true;
    }

    /**
     * Run the customizer framework.
     */
    final public function run_framework(): void {
        static::$types  ??= $this->init_product_types();
        static::$cnames ??= $this->init_product_cnames();
        static::$opts   ??= $this->init_product_opts();
        static::$tabs   ??= $this->init_product_tabs();
        static::$admin  ??= $this->init_product_admin();
    }

    /**
     * Initializes the product classnames array
     *
     * @return array
     */
    protected function init_product_cnames(): array {
        return \wp_filter_object_list( static::$types, array( 'class' => null ), 'not', 'class' );
    }

    /**
     * Initializes the customizer admin instance
     *
     * @return Customizer_Admin
     */
    protected function init_product_admin(): Customizer_Admin {
        return new Customizer_Admin( static::$types, static::$opts, static::$tabs );
    }

    /**
     * Returns the product types array
     *
     * Product type is an array keyed by product type slug, with the following properties:
     *  - **name**:     Product type name.
     *  - **class**:    Product type class name.
     *  - **tabs**:     Array of tabs to add to the product type.
     *  - **extends**: Array of product type slugs from which to inherit the tabs and option visibility
     *
     * @return array
     */
    final protected function init_product_types(): array {
        $types = \apply_filters( 'xwc_product_types', array() );
        $types = $this->parse_product_types( $types );

        return $types;
    }

    /**
     * Parses the product types array
     *
     * @param  array $types Product types array.
     * @return array
     */
    final protected function parse_product_types( array $types ): array {
        foreach ( $types as $type => &$args ) {
            $args = \wp_parse_args(
                $args,
                array(
                    'class'   => null,
                    'extends' => array(),
                    'name'    => null,
                    'tabs'    => array(),
                ),
            );

            $args['extends'] = \wc_string_to_array( $args['extends'] );

            if ( \get_term_by( 'slug', $type, 'product_type' ) ) {
                continue;
            }

            \wp_insert_term( $type, 'product_type' );
        }

        return $types;
    }

    /**
     * Get the product types array.
     *
     * Product type is an array keyed by product type slug, with the following properties:
     *  - **name**:     Product type name.
     *  - **class**:    Product type class name.
     *  - **tabs**:     Array of tabs to add to the product type.
     *  - **extends**: Array of product type slugs from which to inherit the tabs and option visibility
     *
     * You can override a product class by supplying a classname in the `class` property.
     *
     * @param  array<string, array{
     *   name?: string,
     *   class?: class-string,
     *   tabs?: array<int, array{
     *     id: string,
     *     key?: string,
     *     for: string|array<string>,
     *     label: string,
     *     icon?: string,
     *     prio?: int
     *   }>,
     *   extends?: array<string>,
     * }> $types Product types array.
     *
     * @return array<string, array{
     *   name?: string,
     *   class?: class-string,
     *   tabs?: array<int, array{
     *     id: string,
     *     key?: string,
     *     for?: string|array<string>,
     *     label: string,
     *     icon?: string,
     *     prio?: int
     *   }>,
     *   extends?: array<string>
     * }>
     */
    public function custom_product_types( array $types ): array {
        return $types;
    }

    /**
     * Initializes custom product options array
     *
     * @return array
     */
    final protected function init_product_opts(): array {
        $opts = \apply_filters( 'xwc_product_opts', array() );
        $opts = $this->parse_product_opts( $opts );

        return $opts;
    }

    /**
     * Parses the product options array
     *
     * @param  array $opts Product options array.
     * @return array
     */
    final protected function parse_product_opts( array $opts ): array {
        foreach ( $opts as &$args ) {
            $args            = \wp_parse_args(
                $args,
                array(
                    'default' => false,
                    'desc'    => null,
                    'extends' => '',
                    'for'     => array(),
                    'label'   => null,
                    'prop'    => false,
                    'tabs'    => array(),
                ),
            );
            $args['extends'] = \wc_string_to_array( $args['extends'] );
            $args['for']     = $this->parse_opt_for( $args['for'] );
        }

        return $opts;
    }

    /**
     * Parses the product options array
     *
     * @param  string|array $opt      Product option.
     * @param  string       ...$extra Extra options.
     * @return array
     */
    private function parse_opt_for( string|array $opt, string ...$extra ): array {
        $opt = \array_merge( \wc_string_to_array( $opt ), $extra );
        $opt = \array_map( static fn( $t ) => "show_if_{$t}", $opt );

        return \array_values( \array_unique( $opt ) );
    }

    /**
     * Get the product options array
     *
     * Product option is an array keyed by product option slug, with the following properties:
     *  - **id**:      Product option id.
     *  - **label**:   Label for the option.
     *  - **desc**:    Description for the option.
     *  - **for**:     Array of product type slugs for which the option is available.
     *  - **default**: Default value for the option. Can be `yes` or `no`, or a boolean.
     *  - **prop**: Whether the option is a product property, or a meta data
     *
     * @param  array<string, array{
     *   id: string,
     *   label: string,
     *   desc: string,
     *   for: string|array<string>,
     *   default?: bool|string,
     *   prop?: bool,
     *   tabs?: array<array{
     *     id: string,
     *     key?: string,
     *     for: string|array<string>,
     *     label: string,
     *     icon?: string,
     *     prio?: int,
     *   }>,
     * }> $opts Product options array.
     * @return array<string, array{
     *   id: string,
     *   label: string,
     *   desc: string,
     *   for: string|array<string>,
     *   default?: bool|string,
     *   prop?: bool,
     *   tabs?: array<array{
     *     id: string,
     *     key?: string,
     *     label: string,
     *     icon?: string,
     *     prio?: int,
     *   }>,
     * }>
     */
    public function custom_product_opts( array $opts ): array {
        return $opts;
    }

    /**
     * Get the product tabs array
     *
     * @param  array $tabs Product tabs array.
     * @return array<array{
     *     id: string,
     *     key?: string,
     *     label: string,
     *     icon?: string,
     *     prio?: int,
     *     panel?: string
     *     for?: string|array<string>
     *   }>
     */
    public function custom_product_tabs( array $tabs ): array {
        return $tabs;
    }

    /**
     * Initializes the product tabs array
     *
     * @return array
     */
    final protected function init_product_tabs(): array {
        $get = static fn( $a ) => \wp_filter_object_list( $a, array( 'tabs' => array() ), 'not', 'tabs' );

        $tabs = \apply_filters( 'xwc_product_tabs', array() );
        $tabs = \array_merge( $get( static::$types ), $get( static::$opts ), $tabs );
        $tabs = $this->parse_product_tabs( $tabs );

        return $tabs;
    }

    /**
     * Undocumented function
     *
     * @param  array<string, array<array{
     *     id: string,
     *     key?: string,
     *     for?: string|array<string>,
     *     label: string,
     *     icon?: string,
     *     prio?: int,
     *     panel?: string
     *   }>> $all_tabs
     * @return array
     */
    final protected function parse_product_tabs( array $all_tabs ): array {
        $parsed = array();
        foreach ( $all_tabs as $type_opt => $tabs ) {
            foreach ( $tabs as $tab ) {
                $key = $tab['key'] ?? $tab['id'];

                $parsed[ $key ] = array(
                    'class'    => $this->parse_opt_for( $tab['for'] ?? '', $type_opt ),
                    'icon'     => $tab['icon'] ?? '',
                    'id'       => $tab['id'],
                    'label'    => $tab['label'],
                    'panel'    => \wc_string_to_array( $tab['panel'] ?? 'woocommerce_options_panel' ),
                    'priority' => $tab['prio'] ?? 21,
                    'target'   => "{$tab['id']}_product_data",
                );
            }
        }

        return $parsed;
    }

    /**
     * Modifies product classnames.
     *
     * @param  string $classname Product classname.
     * @param  string $type      Product type.
     * @return string
     */
    public function modify_classname( string $classname, string $type ) {
        if ( ! isset( static::$cnames[ $type ] ) ) {
            return $classname;
        }

        return static::$cnames[ $type ];
    }
}
