<?php
/**
 * Plugin Name: Página do Professor
 * Description: Adiciona campos personalizados ao perfil de usuário e fornece shortcode de perfil de servidor.
 * Version: 1.0.1
 * Author: Guilherme Araujo
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

require_once plugin_dir_path( __FILE__ ) . 'pagina professor.php';

add_action( 'show_user_profile', 'plugin_perfil_render_profile_fields' );
add_action( 'edit_user_profile', 'plugin_perfil_render_profile_fields' );
add_action( 'personal_options_update', 'plugin_perfil_save_profile_fields' );
add_action( 'edit_user_profile_update', 'plugin_perfil_save_profile_fields' );

/**
 * Renderiza os campos personalizados no formulário de perfil do usuário.
 *
 * @param WP_User $user Usuário sendo editado.
 */
function plugin_perfil_render_profile_fields( $user ) {
	$cargo_categoria     = get_user_meta( $user->ID, 'plugin_perfil_cargo_categoria', true );
	$titulacao_principal = get_user_meta( $user->ID, 'plugin_perfil_titulacao_principal', true );
	$curriculo_lattes    = get_user_meta( $user->ID, 'plugin_perfil_curriculo_lattes', true );
	$vinculo_institucional = get_user_meta( $user->ID, 'plugin_perfil_vinculo_institucional', true );
	$sobre               = get_user_meta( $user->ID, 'plugin_perfil_sobre', true );
	$atuacao             = get_user_meta( $user->ID, 'plugin_perfil_atuacao', true );
	$historico_formacao  = get_user_meta( $user->ID, 'plugin_perfil_historico_formacao', true );

	if ( ! is_array( $historico_formacao ) ) {
		$historico_formacao = array();
	}
	?>
	<h2><?php esc_html_e( 'Dados Profissionais', 'plugin-perfil' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><label for="plugin_perfil_cargo_categoria"><?php esc_html_e( 'Cargo ou Categoria', 'plugin-perfil' ); ?></label></th>
			<td>
				<input type="text" name="plugin_perfil_cargo_categoria" id="plugin_perfil_cargo_categoria" value="<?php echo esc_attr( $cargo_categoria ); ?>" class="regular-text" />
			</td>
		</tr>

		<tr>
			<th><label for="plugin_perfil_titulacao_principal"><?php esc_html_e( 'Titulação Principal', 'plugin-perfil' ); ?></label></th>
			<td>
				<input type="text" name="plugin_perfil_titulacao_principal" id="plugin_perfil_titulacao_principal" value="<?php echo esc_attr( $titulacao_principal ); ?>" class="regular-text" />
			</td>
		</tr>

		<tr>
			<th><label for="plugin_perfil_curriculo_lattes"><?php esc_html_e( 'Currículo Lattes', 'plugin-perfil' ); ?></label></th>
			<td>
				<input type="url" name="plugin_perfil_curriculo_lattes" id="plugin_perfil_curriculo_lattes" value="<?php echo esc_url( $curriculo_lattes ); ?>" class="regular-text" />
			</td>
		</tr>

		<tr>
			<th><label for="plugin_perfil_vinculo_institucional"><?php esc_html_e( 'Vínculo Institucional', 'plugin-perfil' ); ?></label></th>
			<td>
				<textarea name="plugin_perfil_vinculo_institucional" id="plugin_perfil_vinculo_institucional" rows="4" class="large-text"><?php echo esc_textarea( $vinculo_institucional ); ?></textarea>
			</td>
		</tr>

		<tr>
			<th><label><?php esc_html_e( 'Sobre mim', 'plugin-perfil' ); ?></label></th>
			<td>
				<?php
				wp_editor(
					wp_kses_post( $sobre ),
					'plugin_perfil_sobre',
					array(
						'textarea_name' => 'plugin_perfil_sobre',
						'textarea_rows' => 8,
						'media_buttons' => false,
						'teeny'         => true,
						'tinymce'       => true,
					)
				);
				?>
			</td>
		</tr>

		<tr>
			<th><label><?php esc_html_e( 'Atuação', 'plugin-perfil' ); ?></label></th>
			<td>
				<?php
				wp_editor(
					wp_kses_post( $atuacao ),
					'plugin_perfil_atuacao',
					array(
						'textarea_name' => 'plugin_perfil_atuacao',
						'textarea_rows' => 8,
						'media_buttons' => false,
						'teeny'         => true,
						'tinymce'       => true,
					)
				);
				?>
			</td>
		</tr>

		<tr>
			<th><label><?php esc_html_e( 'Histórico de Formação', 'plugin-perfil' ); ?></label></th>
			<td>
				<?php wp_nonce_field( 'plugin_perfil_profile_nonce', 'plugin_perfil_profile_nonce' ); ?>
				<div id="plugin-perfil-historico-formacao" class="plugin-perfil-repeater">
					<div class="plugin-perfil-repeater-rows">
						<?php if ( empty( $historico_formacao ) ) : ?>
							<div class="plugin-perfil-repeater-row">
								<div class="plugin-perfil-repeater-fields">
									<label>
										<span><?php esc_html_e( 'Ano', 'plugin-perfil' ); ?></span>
										<input type="text" name="plugin_perfil_historico_formacao[0][ano]" value="" />
									</label>
									<label>
										<span><?php esc_html_e( 'Título', 'plugin-perfil' ); ?></span>
										<input type="text" name="plugin_perfil_historico_formacao[0][titulo]" value="" />
									</label>
									<label>
										<span><?php esc_html_e( 'Instituição', 'plugin-perfil' ); ?></span>
										<input type="text" name="plugin_perfil_historico_formacao[0][instituicao]" value="" />
									</label>
								</div>
								<button type="button" class="button plugin-perfil-remove-row"><?php esc_html_e( 'Remover', 'plugin-perfil' ); ?></button>
							</div>
						<?php else : ?>
							<?php foreach ( $historico_formacao as $index => $item ) : ?>
								<div class="plugin-perfil-repeater-row">
									<div class="plugin-perfil-repeater-fields">
										<label>
											<span><?php esc_html_e( 'Ano', 'plugin-perfil' ); ?></span>
											<input type="text" name="plugin_perfil_historico_formacao[<?php echo esc_attr( $index ); ?>][ano]" value="<?php echo esc_attr( $item['ano'] ?? '' ); ?>" />
										</label>
										<label>
											<span><?php esc_html_e( 'Título', 'plugin-perfil' ); ?></span>
											<input type="text" name="plugin_perfil_historico_formacao[<?php echo esc_attr( $index ); ?>][titulo]" value="<?php echo esc_attr( $item['titulo'] ?? '' ); ?>" />
										</label>
										<label>
											<span><?php esc_html_e( 'Instituição', 'plugin-perfil' ); ?></span>
											<input type="text" name="plugin_perfil_historico_formacao[<?php echo esc_attr( $index ); ?>][instituicao]" value="<?php echo esc_attr( $item['instituicao'] ?? '' ); ?>" />
										</label>
									</div>
									<button type="button" class="button plugin-perfil-remove-row"><?php esc_html_e( 'Remover', 'plugin-perfil' ); ?></button>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<button type="button" class="button button-secondary plugin-perfil-add-row"><?php esc_html_e( 'Adicionar Formação', 'plugin-perfil' ); ?></button>
					<script type="text/template" id="plugin-perfil-row-template">
						<div class="plugin-perfil-repeater-row">
							<div class="plugin-perfil-repeater-fields">
								<label>
									<span><?php esc_html_e( 'Ano', 'plugin-perfil' ); ?></span>
									<input type="text" name="plugin_perfil_historico_formacao[__INDEX__][ano]" value="" />
								</label>
								<label>
									<span><?php esc_html_e( 'Título', 'plugin-perfil' ); ?></span>
									<input type="text" name="plugin_perfil_historico_formacao[__INDEX__][titulo]" value="" />
								</label>
								<label>
									<span><?php esc_html_e( 'Instituição', 'plugin-perfil' ); ?></span>
									<input type="text" name="plugin_perfil_historico_formacao[__INDEX__][instituicao]" value="" />
								</label>
							</div>
							<button type="button" class="button plugin-perfil-remove-row"><?php esc_html_e( 'Remover', 'plugin-perfil' ); ?></button>
						</div>
					</script>
				</div>
			</td>
		</tr>
	</table>

	<style>
		.plugin-perfil-repeater .plugin-perfil-repeater-rows {
			display: grid;
			gap: 12px;
		}
		.plugin-perfil-repeater-row {
			display: flex;
			gap: 12px;
			align-items: flex-end;
			padding: 12px;
			border: 1px solid #dcdcde;
			border-radius: 4px;
			background: #fff;
		}
		.plugin-perfil-repeater-fields {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
			gap: 10px;
			flex: 1;
		}
		.plugin-perfil-repeater-fields label {
			display: flex;
			flex-direction: column;
			gap: 4px;
			font-weight: 600;
		}
		.plugin-perfil-repeater-fields input {
			width: 100%;
		}
	</style>

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			var repeater = document.getElementById('plugin-perfil-historico-formacao');
			if (!repeater) {
				return;
			}

			var rowsContainer = repeater.querySelector('.plugin-perfil-repeater-rows');
			var addButton = repeater.querySelector('.plugin-perfil-add-row');
			var template = document.getElementById('plugin-perfil-row-template');

			if (!rowsContainer || !addButton || !template) {
				return;
			}

			addButton.addEventListener('click', function () {
				var count = rowsContainer.querySelectorAll('.plugin-perfil-repeater-row').length;
				var html = template.innerHTML.replace(/__INDEX__/g, count);
				rowsContainer.insertAdjacentHTML('beforeend', html);
			});

			rowsContainer.addEventListener('click', function (event) {
				if (event.target.classList.contains('plugin-perfil-remove-row')) {
					event.target.closest('.plugin-perfil-repeater-row').remove();
				}
			});
		});
	</script>
	<?php
}

/**
 * Salva os campos personalizados no banco de dados do usuário.
 *
 * @param int $user_id ID do usuário.
 */
function plugin_perfil_save_profile_fields( $user_id ) {
	if ( ! isset( $_POST['plugin_perfil_profile_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['plugin_perfil_profile_nonce'] ) ), 'plugin_perfil_profile_nonce' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	$cargo_categoria     = isset( $_POST['plugin_perfil_cargo_categoria'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_perfil_cargo_categoria'] ) ) : '';
	$titulacao_principal = isset( $_POST['plugin_perfil_titulacao_principal'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_perfil_titulacao_principal'] ) ) : '';
	$curriculo_lattes    = isset( $_POST['plugin_perfil_curriculo_lattes'] ) ? esc_url_raw( wp_unslash( $_POST['plugin_perfil_curriculo_lattes'] ) ) : '';
	$vinculo_institucional = isset( $_POST['plugin_perfil_vinculo_institucional'] ) ? sanitize_textarea_field( wp_unslash( $_POST['plugin_perfil_vinculo_institucional'] ) ) : '';
	$sobre               = isset( $_POST['plugin_perfil_sobre'] ) ? wp_kses_post( wp_unslash( $_POST['plugin_perfil_sobre'] ) ) : '';
	$atuacao             = isset( $_POST['plugin_perfil_atuacao'] ) ? wp_kses_post( wp_unslash( $_POST['plugin_perfil_atuacao'] ) ) : '';

	update_user_meta( $user_id, 'plugin_perfil_cargo_categoria', $cargo_categoria );
	update_user_meta( $user_id, 'plugin_perfil_titulacao_principal', $titulacao_principal );
	update_user_meta( $user_id, 'plugin_perfil_curriculo_lattes', $curriculo_lattes );
	update_user_meta( $user_id, 'plugin_perfil_vinculo_institucional', $vinculo_institucional );
	update_user_meta( $user_id, 'plugin_perfil_sobre', $sobre );
	update_user_meta( $user_id, 'plugin_perfil_atuacao', $atuacao );

	$historico_formacao = array();
	if ( isset( $_POST['plugin_perfil_historico_formacao'] ) && is_array( $_POST['plugin_perfil_historico_formacao'] ) ) {
		foreach ( $_POST['plugin_perfil_historico_formacao'] as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$historico_formacao[] = array(
				'ano'          => isset( $row['ano'] ) ? sanitize_text_field( wp_unslash( $row['ano'] ) ) : '',
				'titulo'       => isset( $row['titulo'] ) ? sanitize_text_field( wp_unslash( $row['titulo'] ) ) : '',
				'instituicao'  => isset( $row['instituicao'] ) ? sanitize_text_field( wp_unslash( $row['instituicao'] ) ) : '',
			);
		}
	}

	$historico_formacao = array_values(
		array_filter(
			$historico_formacao,
			function ( $item ) {
				return ! empty( $item['ano'] ) || ! empty( $item['titulo'] ) || ! empty( $item['instituicao'] );
			}
		)
	);

	update_user_meta( $user_id, 'plugin_perfil_historico_formacao', $historico_formacao );
}
