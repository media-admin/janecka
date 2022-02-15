<?php
if(!defined('ABSPATH')) {
    die();
}
?>
<div class="wp_all_export_rule_inputs">
	<table>
		<tr>
			<th><?php esc_html_e('Element', 'wp_all_export_plugin'); ?></th>
			<th><?php esc_html_e('Rule', 'wp_all_export_plugin'); ?></th>
			<th><?php esc_html_e('Value', 'wp_all_export_plugin'); ?></th>
			<th>&nbsp;</th>
		</tr>
		<tr>
			<td style="width: 25%;">
				<select id="wp_all_export_xml_element">
					<option value=""><?php esc_html_e('Select Element', 'wp_all_export_plugin'); ?></option>
					<?php
                        // The filters are sanitized in the render_filters() method
                        echo $engine->render_filters();
                    ?>
				</select>
			</td>
			<td style="width: 25%;" id="wp_all_export_available_rules">
				<select id="wp_all_export_rule">
					<option value=""><?php esc_html_e('Select Rule', 'wp_all_export_plugin'); ?></option>
				</select>
			</td>
			<td style="width: 25%;">
				<input id="wp_all_export_value" type="text" placeholder="value" value=""/>
			</td>
			<td style="width: 15%;">
				<a id="wp_all_export_add_rule" href="javascript:void(0);"><?php esc_html_e('Add Rule', 'wp_all_export_plugin');?></a>
			</td>
		</tr>
	</table>						
</div>	
<div id="wpallexport-filters" style="padding:0;">								
	<div class="wpallexport-content-section" style="padding:0; border: none;">					
		<fieldset id="wp_all_export_filtering_rules">					
			<?php
			$filter_rules = $post['filter_rules_hierarhy'];
			$filter_rules_hierarhy = json_decode($filter_rules);											
			?>
			<p id="date_field_notice" style="margin: 5px 0px 20px; text-align: center;"><?php wp_kses_post(__('Date filters use natural language.<br>For example, to return records created in the last week: <i>date ▸ newer than ▸ last week</i>.<br>For all records created in 2016: <i>date ▸ older than ▸ 1/1/2017</i> AND <i>date ▸ newer than ▸ 12/31/2015</i>', 'wp_all_export_plugin'));?>.</p>
			<p id="no_options_notice" style="margin:20px 0 5px; text-align:center; <?php if ( ! empty($filter_rules_hierarhy) and is_array($filter_rules_hierarhy) ) echo 'display:none;';?>"><?php esc_html_e('No filtering options. Add filtering options to only export records matching some specified criteria.', 'wp_all_export_plugin');?></p>
			<ol class="wp_all_export_filtering_rules">
				<?php							
					
					$condition_labels = array(
						'default' => array(
							'equals' => esc_html__('equals', 'wp_all_export_plugin'),
							'not_equals' => esc_html__("doesn't equal", 'wp_all_export_plugin'),
							'greater' => esc_html__('greater than', 'wp_all_export_plugin'),
							'equals_or_greater' => esc_html__('equal to or greater than', 'wp_all_export_plugin'),
							'less' => esc_html__('less than', 'wp_all_export_plugin'),
							'equals_or_less' => esc_html__('equal to or less than', 'wp_all_export_plugin'),
							'contains' => esc_html__('contains', 'wp_all_export_plugin'),
							'not_contains' => esc_html__("doesn't contain", 'wp_all_export_plugin'),
							'is_empty' => esc_html__('is empty', 'wp_all_export_plugin'),
							'is_not_empty' => esc_html__('is not empty', 'wp_all_export_plugin'),
							'in' => esc_html__('In', 'wp_all_export_plugin'),
							'not_in' => esc_html__('Not In', 'wp_all_export_plugin')
						),
						'date' => array(
							'equals' => esc_html__('equals', 'wp_all_export_plugin'),
							'not_equals' => esc_html__("doesn't equal", 'wp_all_export_plugin'),
							'greater' => esc_html__('newer than', 'wp_all_export_plugin'),
							'equals_or_greater' => esc_html__('equal to or newer than', 'wp_all_export_plugin'),
							'less' => esc_html__('older than', 'wp_all_export_plugin'),
							'equals_or_less' => esc_html__('equal to or older than', 'wp_all_export_plugin'),
							'contains' => esc_html__('contains', 'wp_all_export_plugin'),
							'not_contains' => esc_html__("doesn't contain", 'wp_all_export_plugin'),
							'is_empty' => esc_html__('is empty', 'wp_all_export_plugin'),
							'is_not_empty' => esc_html__('is not empty', 'wp_all_export_plugin'),
							'in' => esc_html__('In', 'wp_all_export_plugin'),
							'not_in' => esc_html__('Not In', 'wp_all_export_plugin')
						)
					);

					if ( ! empty($filter_rules_hierarhy) and is_array($filter_rules_hierarhy) ): 

						$rulenumber = 0;
						
						foreach ($filter_rules_hierarhy as $rule) 
						{						
							if ( is_null($rule->parent_id) )
							{								
								$condition_label = in_array($rule->element, array('post_date', 'user_registered', 'comment_date')) ? $condition_labels['date'][$rule->condition] : $condition_labels['default'][$rule->condition];

								$rulenumber++;
								?>
								<li id="item_<?php echo intval($rulenumber);?>" class="dragging">
									<div class="drag-element">
										<input type="hidden" value="<?php echo esc_attr($rule->element); ?>" class="wp_all_export_xml_element" name="wp_all_export_xml_element[<?php echo intval($rulenumber); ?>]"/>
										<input type="hidden" value="<?php echo esc_attr($rule->title); ?>" class="wp_all_export_xml_element_title" name="wp_all_export_xml_element_title[<?php echo intval($rulenumber); ?>]"/>
							    		<input type="hidden" value="<?php echo esc_attr($rule->condition); ?>" class="wp_all_export_rule" name="wp_all_export_rule[<?php echo intval($rulenumber); ?>]"/>
										<input type="hidden" value="<?php echo esc_attr($rule->value); ?>" class="wp_all_export_value" name="wp_all_export_value[<?php echo intval($rulenumber); ?>]"/>
										<span class="rule_element"><?php echo esc_html($rule->title); ?></span>
										<span class="rule_as_is"><?php echo esc_html($condition_label); ?></span>
										<span class="rule_condition_value"><?php echo esc_html($rule->value); ?></span>
										<span class="condition <?php if ($rulenumber == count($filter_rules_hierarhy)) :?>last_condition<?php endif; ?>">
											<label for="rule_and_<?php echo intval($rulenumber); ?>">AND</label>
											<input id="rule_and_<?php echo intval($rulenumber); ?>" type="radio" value="and" name="rule[<?php echo intval($rulenumber); ?>]" <?php if ($rule->clause == 'AND'): ?>checked="checked"<?php endif; ?> class="rule_condition"/>
											<label for="rule_or_<?php echo intval($rulenumber); ?>">OR</label>
											<input id="rule_or_<?php echo intval($rulenumber); ?>" type="radio" value="or" name="rule[<?php echo intval($rulenumber); ?>]" <?php if ($rule->clause == 'OR'): ?>checked="checked"<?php endif; ?> class="rule_condition"/>
										</span>
									</div>
									<a href="javascript:void(0);" class="icon-item remove-ico"></a>
									<?php echo wp_all_export_reverse_rules_html($filter_rules_hierarhy, $rule, $rulenumber, $condition_labels); ?>
								</li>
								<?php
							}
						}
					endif;
				?>
			</ol>	
			<div class="clear"></div>
		</fieldset>
	</div>	
</div>