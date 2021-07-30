<?php
require_once("../../wp-load.php");
require_once('../../wp-admin/includes/file.php');
require_once('../../wp-admin/includes/image.php' );
set_time_limit(0);
ini_set('display_errors', 1);

$args = array(
	'post_type' => wc_get_order_types(),
	'post_status' => array_keys( wc_get_order_statuses() ),
	'date_query' => array(
        array(
            'after'    => ['year' => 2021, 'month' => 1, 'day' => 1],
			'column' => 'post_modified_gmt'
        ),
    )
);
$consulta_pedidos = new WP_Query($args);

$arrPedidosWoocommerce = $consulta_pedidos->posts;
$arrPedidos = array();

foreach ($arrPedidosWoocommerce as $pedido) {
	
	$order = wc_get_order( $pedido );
	
	$order_data = $order->get_data();
	
	$order_meta = get_post_meta( $pedido->ID);

	$cliente_id = $order->get_customer_id();
	$order_billing_cpf = get_user_meta( $cliente_id, 'billing_cpf',true);
	$order_billing_cnpj = get_user_meta( $cliente_id, 'billing_cnpj',true);
		
	if (empty($order_billing_cpf) && empty($order_billing_cnpj)) {
		continue;
	}
	
	
	$email = $order_meta['_billing_email'][0];
	$order_billing_cpf_cnpj = $order_meta['0']['_billing_cnpj'];
	$order_billing_first_name = $order_data['billing']['first_name'];
	$order_billing_last_name = $order_data['billing']['last_name'];
	
	$order_billing_company = $order_data['billing']['company'];
	$order_billing_address_1 = $order_data['billing']['address_1'];
	$order_billing_address_2 = $order_data['billing']['address_2'];
	$order_billing_address_number = get_user_meta( $cliente_id, 'shipping_number',true);
	$order_billing_address_bairro = get_user_meta( $cliente_id, 'shipping_neighborhood',true);
	$order_billing_city = $order_data['billing']['city'];
	$order_billing_state = $order_data['billing']['state'];
	$order_billing_postcode = $order_data['billing']['postcode'];
	$order_billing_country = $order_data['billing']['country'];
	$order_billing_email = $order_data['billing']['email'];
	$order_billing_phone = $order_data['billing']['phone'];	
	$order_billing_cellphone = get_user_meta( $cliente_id, 'billing_cellphone',true);
	
	$order_shipping_cpf_cnpj = $order_meta['0']['_shipping_cnpj'];
	$order_shipping_first_name = $order_data['shipping']['first_name'];
	$order_shipping_last_name = $order_data['shipping']['last_name'];
	
	$order_shipping_company = $order_data['shipping']['company'];
	$order_shipping_address_1 = $order_data['shipping']['address_1'];
	$order_shipping_address_2 = $order_data['shipping']['address_2'];
	$order_shipping_address_number = get_user_meta( $cliente_id, 'shipping_number',true);
	$order_shipping_address_bairro = get_user_meta( $cliente_id, 'shipping_neighborhood',true);
	$order_shipping_city = $order_data['shipping']['city'];
	$order_shipping_state = $order_data['shipping']['state'];
	$order_shipping_postcode = $order_data['shipping']['postcode'];
	$order_shipping_country = $order_data['shipping']['country'];
	$order_shipping_cellphone = get_user_meta( $cliente_id, 'shipping_cellphone',true);	
	
	
	$numero_pedido_tsd = isset($order_meta['_numero_pedido_tsd']) ? $order_meta['_numero_pedido_tsd'][0] : 0;
	
	$order_billing_payment_method = $order->get_payment_method();
		
	if (!empty($order_billing_cnpj)) {
		$documento = $order_billing_cnpj;
	} else {
		$documento = $order_billing_cpf;
	}
	
	$notificacoes_pedido = wc_get_order_notes(['order_id' => $pedido->ID]);
	$notificacoes = '';
	if (is_array($notificacoes_pedido)) {
		foreach ($notificacoes_pedido as $nota) {
			$notificacoes .= $nota->content . "\n\r";
		}
	}
	
	
	
	$arrPedido['cliente'] = array(
					'codigo_cliente' => (string) $cliente_id,
					'nome_fantasia' => $order_billing_first_name.' '.$order_billing_last_name,
					'email' => $order_billing_email,
					'razao_social' => $order_billing_first_name.' '.$order_billing_last_name,
					'cnpj_cpf' => $documento,
					'endereco' => $order_billing_address_1,
					'bairro' => $order_billing_address_bairro,
					'numero' => $order_billing_address_number,
					'complemento' => $order_billing_address_2,
					'estado' => $order_billing_state,
					'cep' => $order_billing_postcode,
					'telefone1' => $order_billing_phone,
					'telefone2' => $order_billing_cellphone,
					'cidade' => $order_billing_city,
					'dados_entrega' => [
						'nome_fantasia' => $order_shipping_first_name.' '.$order_shipping_last_name,
						'razao_social' => $order_shipping_first_name.' '.$order_shipping_last_name,
						'cnpj_cpf' => $documento,
						'endereco' => $order_shipping_address_1,
						'bairro' => $order_shipping_address_bairro,
						'numero' => $order_shipping_address_number,
						'complemento' => $order_shipping_address_2,
						'estado' => $order_shipping_state,
						'cep' => $order_shipping_postcode,
						'cidade' => $order_shipping_city					
					]
				);

	$carrinho = $order_data['line_items'];
	$carrinho = $order->get_items();
	$quantidade_itens = count($carrinho);
	$arrProdutos = array();
	

	$valor_total_pedido = 0;
	foreach ($carrinho as $item_carrinho) {
			$product = new WC_Product($item_carrinho['product_id']);
			$arrProdutos[] = array(
									"codigo_produto" => $product->get_sku(),
									"descricao" => $item_carrinho['name'],
									"quantidade" => $item_carrinho['qty'],
									"valor_mercadoria" => $item_carrinho['subtotal'],
									"valor_total" => $item_carrinho['qty'] * $item_carrinho['subtotal'],
									"valor_unitario" => $item_carrinho['subtotal'],
								);
			$valor_total_pedido = $item_carrinho['qty'] * $item_carrinho['subtotal'];
	}
	$arrPedido['produto'] = $arrProdutos;
	
	
	$arrFormasDeEntrega = [];
	foreach( $order->get_items( 'shipping' ) as $item_id => $forma_entrega ){
		$dados_forma_entrega = $forma_entrega->get_data();
		$arrFormasDeEntrega[] = [
			'codigo_forma_entrega' => $dados_forma_entrega['method_id'],
			'descricao_forma_entrega' => $dados_forma_entrega['method_title'],
			'valor_total' => $dados_forma_entrega['total'],
			'valor_taxas' => $dados_forma_entrega['total_tax'],
		];
	}	
	$arrPedido['pedido'] = array(
											"numero_pedido"=> $pedido->ID,
											"quantidade_itens"=> $quantidade_itens,
											"valor_total" => $valor_total_pedido,
											"forma_pagamento" => $order_billing_payment_method,
											"data_venda" => $order->get_date_created(),
											"observacao" => $order->get_customer_note(),
											"notificacoes" => $notificacoes,
											"numero_pedido_tsd" => $numero_pedido_tsd,
											"forma_entrega" => $arrFormasDeEntrega
										);
	
	$arrPedidos[] = $arrPedido;
}

echo json_encode($arrPedidos);
