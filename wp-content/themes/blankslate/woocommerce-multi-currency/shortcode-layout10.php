<?php
/** @var  $dropdown_icon */
/** @var  $data_flag_size */
/** @var  $custom_format */
/** @var  $country_code */
/** @var  $flag_size */
/** @var  $symbol */
?>
    <div id="<?php echo esc_attr( $id ) ?>"
         class="woocommerce-multi-currency wmc-shortcode plain-vertical layout10 <?php echo esc_attr( $class ) ?>"
         data-layout="layout10" data-flag_size="<?php echo esc_attr( $data_flag_size ) ?>"
         data-dropdown_icon="<?php echo esc_attr( $dropdown_icon ) ?>"
         data-custom_format="<?php echo esc_attr( $custom_format ) ?>">
        <input type="hidden" class="wmc-current-url" value="<?php echo esc_attr( $current_url ) ?>">
        <div class="wmc-currency-wrapper">
				<span class="wmc-current-currency" style="line-height: <?php echo esc_attr( $line_height ) ?>">
                   <span>
                    <?php
                    echo "<i style='" . esc_attr( $flag_size ) . "' class='wmc-current-flag vi-flag-64 flag-" . esc_attr( $country_code ) . "'></i>";
                    $display_name = apply_filters( 'wmc_shortcode_currency_display_name', $countries[ $current_currency ], $current_currency );
                    if ( $custom_format ) {
	                    ?>
                        <span class="<?php echo esc_attr( "wmc-text wmc-text-{$current_currency}" ) ?>">
                            <?php
                            echo str_replace( array(// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	                            '{currency_name}',
	                            '{currency_code}',
	                            '{currency_symbol}'
                            ), array(
	                            '<span class="wmc-currency-name">' . esc_html( $display_name ) . '</span>',
	                            '<span class="wmc-currency-code">' . esc_html( $current_currency ) . '</span>',
	                            '<span class="wmc-currency-symbol">' . esc_html( $symbol ) . '</span>'
                            ), $custom_format );
                            ?>
                        </span>
	                    <?php
                    } else {
	                    echo "<span class='wmc-text wmc-text-" . esc_attr( $current_currency ) . "'>
                                <span class='wmc-text-currency-text'>" . esc_html( $current_currency ) . "</span>
                                <span class='wmc-text-currency-symbol'>(" . esc_html( $symbol ) . ")</span>
                            </span>";
                    }
                    ?>
                    </span>
                    <?php //echo $arrow;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<svg class="wmc-open-dropdown-currencies" width="8" height="4" viewBox="0 0 8 4" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M6.57055 0.484914L6.57048 0.484981L4.00283 2.88963L1.43008 0.484915C1.30895 0.371695 1.11487 0.371695 0.993737 0.484915C0.868754 0.601735 0.868754 0.794401 0.993738 0.911221L3.77956 3.51509C3.84043 3.57198 3.91693 3.6 3.99773 3.6C4.07213 3.6 4.15419 3.57277 4.2159 3.51509L7.00073 0.912151C7.13206 0.795407 7.13202 0.601868 7.00689 0.484914C6.88576 0.371695 6.69168 0.371695 6.57055 0.484914Z" fill="#353D3B" stroke="#353D3B" stroke-width="0.2"/>
					</svg>
                </span>
            <div class="wmc-sub-currency">
				<?php
				foreach ( $links as $k => $link ) {
					$sub_class = array( 'wmc-currency' );
					if ( $current_currency == $k ) {
						$sub_class[] = 'wmc-active';
					}
					$country = $settings->get_country_data( $k );
					?>
                    <div class="<?php echo esc_attr( implode( ' ', $sub_class ) ) ?>" data-currency="<?php echo esc_attr( $k ) ?>">
						<?php
						$html = '';
						if ( $settings->enable_switch_currency_by_js() ) {
							$link = '#';
						}

						$symbol = get_woocommerce_currency_symbol( $k );
						$html   .= sprintf( "<a rel='nofollow' class='wmc-currency-redirect' href='%1s' style='line-height:%2s' data-currency='%3s' data-currency_symbol='%4s'>",
							esc_url( $link ), $line_height, $k, $symbol );
						$html   .= sprintf( "<i style='%1s' class='vi-flag-64 flag-%2s'></i>", $flag_size, strtolower( $country['code'] ) );

						$s_display_name = apply_filters( 'wmc_shortcode_currency_display_name', $countries[ $k ], $k );
						if ( $custom_format ) {
							$html .= '<span>' . str_replace(
									[
										'{currency_name}',
										'{currency_code}',
										'{currency_symbol}'
									],
									[
										'<span class="wmc-sub-currency-name">' . $s_display_name . '</span>',
										'<span class="wmc-sub-currency-code">' . $k . '</span>',
										'<span class="wmc-sub-currency-symbol">' . $symbol . '</span>'
									], $custom_format ) . '</span>';
						} else {
							$html .= sprintf( "<span class='wmc-sub-currency-name'>%1s</span>", esc_html( $k ) );
							$html .= sprintf( "<span class='wmc-sub-currency-symbol'>(%1s)</span>", esc_html( $symbol ) );
						}
						$html .= '</a>';
						echo $html;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
                    </div>
					<?php
				}
				?>
            </div>
        </div>
    </div>
<?php