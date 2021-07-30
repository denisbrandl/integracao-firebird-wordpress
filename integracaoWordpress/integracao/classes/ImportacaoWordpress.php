<?php
$path = dirname(__DIR__);
require_once($path."/../wp-load.php");
require_once($path.'/../wp-admin/includes/file.php');
require_once($path.'/../wp-admin/includes/image.php' );

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

class ImportacaoWordpress {

	private $colunas = array(
								'dados_gerais__id_original',
								'dados_gerais__sku',
								'dados_gerais__nome',
								'dados_gerais__descricao_completa',
								'dados_gerais__altura',
								'dados_gerais__largura',
								'dados_gerais__profundidade',
								'dados_gerais__peso',
								'dados_gerais__preco_normal',
								// 'dados_gerais__preco_oferta',
								'dados_gerais__quantidade',
								'dados_gerais__disponivel',
								'categorias__nome',
							);	
								
    private function SearchBOM($string) { 
       return preg_replace("/&#?[a-z0-9]+;/i","",$string);
    }
	private function validarCabecalho($cabecalho) {
		$mensagem_retorno = '';
		$retornar = 0;
		foreach($cabecalho as $_coluna) {
			$coluna = preg_replace("/&#?[a-z0-9]+;/i","",trim($_coluna));
			if (!in_array($coluna,$this->colunas)) {
				$mensagem_retorno .= 'Linha 0: Coluna '.$_coluna.' incorreta!<br>';
				$retornar = 1;
			}
		}
		foreach($this->colunas as $coluna) {
			if (!in_array($coluna,$cabecalho)) {
				$mensagem_retorno .= 'Linha 0: Coluna '.$coluna.' inexistente!<br>';
				$retornar = 1;
			}
		}
		return array('mensagem_retorno' => $mensagem_retorno, 'retornar' => $retornar);

	}
	
	private function validarCategorias($arrCategorias) {
		$mensagem_retorno = '';
		$retornar = 0;
		$aux = 1;
		foreach($arrCategorias as $categorias_importacao) {
			foreach ($categorias_importacao as $categoria) {
				if (trim($categoria) == "") 
					continue;
				
				if (!in_array(utf8_encode(trim($categoria)), $this->categorias)) {
					$mensagem_retorno .= 'Linha '.$aux.': Categoria '.$categoria.' incorreta!<br>';
					$retornar = 1;
				}
			}
			$aux++;
		}
		return array('mensagem_retorno' => $mensagem_retorno, 'retornar' => $retornar);

	}	


	public function lerCsv($arquivo) {		
		$fp = fopen($arquivo,'r');
		$arrCategorias = array();
		if ($fp) {
			$arquivoCsv = fgetcsv($fp,0,';','"');
			$validacaoCabecalho = $this->validarCabecalho($arquivoCsv);

			if ($validacaoCabecalho['retornar'] == 1) {
				return $this->retorno(0,$validacaoCabecalho['mensagem_retorno']);
			}

			$num_linha = 0;
			$arrLinhas = array();
			while ( ($linha = fgetcsv($fp,0,';','"')) !== FALSE) {
				$arrCategorias[] = explode(',',strtolower($linha[16]));
				$arrLinhas[] = $linha;
				$num_linha++;
			}
			$validacaoCategorias = $this->validarCategorias($arrCategorias);

			if ($validacaoCategorias['retornar'] == 1) {
				return $this->retorno(0,$validacaoCategorias['mensagem_retorno']);
			}
			$this->montaArray($arrLinhas);
			fclose($fp);
			return $this->retorno(1,'Arquivo importado com sucesso!');
		} else {
			return $this->retorno(0,'Erro ao abrir o arquivo');
		}
	}

	public function montaArray($linhas) {
		$arrProdutos = array();
		$totalLinhas = count($linhas);		
		for ($i = 0;$i < $totalLinhas; $i++) {
			$arrProduto = array();
			for ($c=0;$c<16;$c++) {
				$coluna = str_replace('dados_gerais__','',$this->colunas[$c]);
				$arrProduto['dados_gerais'][$coluna] = utf8_encode($linhas[$i][$c]);
			}
			$arrCategorias = explode(',',$linhas[$i][16]);
			$aux = 0;
			foreach ($arrCategorias as $categoria_produto) {
				$arrProduto['categorias'][$aux]['nome'] = utf8_encode(trim($categoria_produto));				
				$arrProduto['categorias'][$aux]['descricao'] = '';				
				$aux++;
			}
			
			$arrProdutos[] = $arrProduto;				
		}
		$produtos = json_encode($arrProdutos);
		$importacao = $this->importarProdutos($produtos);	
	}

	public function lerJson($produtos) {
		foreach ($produtos as $produto) {
			$arrCabecalho = [];
			foreach($produto['dados_gerais'] as $indice => $valor) {
				$arrCabecalho[] = 'dados_gerais__'.$indice;
			}
			if (isset($produto['categorias']) && !empty($produto['categorias'])) {
				$arrCabecalho[] = 'categorias__nome';
			}
			
			$validacaoCabecalho = $this->validarCabecalho($arrCabecalho);
			
			if ($validacaoCabecalho['retornar'] == 1) {
				return $this->retorno(0,$validacaoCabecalho['mensagem_retorno']);				
			}		
		}		
		$importacao = $this->importarProdutos(json_encode($produtos));
		return $this->retorno(1, $importacao);
	}
	
	public function lerExcel($arquivo) {
		if ($arquivoExcel = SimpleXLSX::parse($arquivo)) {
			$arrCategorias = array();
			$linhas = $arquivoExcel->rows();
			$totalLinhas = count($linhas);
			$retornar = false;
			$arrProdutos = array();
			$validacaoCabecalho = $this->validarCabecalho($linhas[0]);

			if ($validacaoCabecalho['retornar'] == 1) {
				return $this->retorno(0,$validacaoCabecalho['mensagem_retorno']);				
			}			 
		
			$arrLinhas = array();
			for ($i = 1;$i < $totalLinhas; $i++) {
				$arrLinhas[] = $linhas[$i];
				$arrCategorias[] = explode(',',strtolower($linhas[$i][16]));
			}
			
			$validacaoCategorias = $this->validarCategorias($arrCategorias);

			if ($validacaoCategorias['retornar'] == 1) {
				return $this->retorno(0,$validacaoCategorias['mensagem_retorno']);
			}			

			$this->montaArray($arrLinhas);
			return $this->retorno(1,'Planilha importada com sucesso!');
		} else {
			return $this->retorno(0,'Erro ao abrir a planilha');
		}
	}
	
	public function retorno($sucesso,$mensagem) {
		return array('sucesso' => $sucesso, 'msgRetorno'=> $mensagem);
	}
	
	public function importarProdutos($produtos) {
		$arrCategorias = array();
		$arrRetorno = array();
		$arrUpload_dir = wp_upload_dir();
		$upload_dir = $arrUpload_dir['basedir'].'/'.date('Y/m').'/';
		$upload_url = $arrUpload_dir['baseurl'].'/';			
		
		if (!$this->validaJson($produtos)) {
			print "JSON INVALIDO";
			return false;
		}		
		$arrProdutos = json_decode($produtos, true);
		foreach ($arrProdutos as $produto) {
			if (isset($produto['categorias']) && count($produto['categorias']) > 0) {
				foreach ($produto['categorias'] as $categoria) {
					$categoria['nome'] = strtolower($categoria['nome']);
					
					$categoria_existe = term_exists( $categoria['nome'], 'product_cat' );
					if ( $categoria_existe !== 0 && $categoria_existe !== null ) {
						wp_update_term(
							$categoria_existe['term_id'],
							strtolower($categoria['nome']),
								'product_cat',
									array(
									'description'=> wp_strip_all_tags($categoria['descricao']),
									'slug' => strtolower($categoria['nome']),
									'parent' => 0
								)
						);
					} else {					
						$marca = wp_insert_term(
							strtolower($categoria['nome']),
							'product_cat',
								array(
								'description'=> wp_strip_all_tags($categoria['descricao']),
								'slug' => strtolower($categoria['nome']),
								'parent' => 0
							)
						);
					}
					$arrCategorias[] = strtolower($categoria['nome']);
				}
			}
			$query = array(
				'author'   => 1,
				'post_type'   => 'product',		
				'meta_key'   					=> '_sku',
				'meta_value'					=> $produto['dados_gerais']['sku']
			);	
			$produtos = new WP_Query($query);
			$novo_produto = true;
			if ($produtos->have_posts() == false) {
				$post = array(
					'post_author' => 1,
					'post_status' => $produto['dados_gerais']['disponivel'] == 'ATIVO' ? 'publish' : "draft",
					'post_title' => wp_strip_all_tags($produto['dados_gerais']['nome']),
					'post_content' => $produto['dados_gerais']['descricao_completa'],
					'post_type' => "product"
				);				

				$product_id = $post_id = wp_insert_post( $post);
			} else {
				$product_id = $produtos->post->ID;
				$post = array(
					'ID' => $product_id,
					'post_author' => 1,
					'post_status' => $produto['dados_gerais']['disponivel'] == 'ATIVO' ? 'publish' : "draft",
					'post_title' => wp_strip_all_tags($produto['dados_gerais']['nome']),
					'post_parent' => '',
					'post_content' => $produto['dados_gerais']['descricao_completa'],
					'post_type' => "product",
					'comment_status' => "open"
				);					
				$post_id = wp_update_post( $post );
				
				$novo_produto = false; 
			}

			wp_set_object_terms($post_id, $arrCategorias, 'product_cat' );
			wp_set_object_terms($post_id, 'simple', 'product_type');			
			
			update_post_meta( $post_id, '_visibility', 'visible' );
			if ($produto['dados_gerais']['quantidade'] > 0) {
				update_post_meta( $post_id, '_stock_status', 'instock');
			}
			update_post_meta( $post_id, 'total_sales', '0');
			update_post_meta( $post_id, '_downloadable', 'no');
			update_post_meta( $post_id, '_virtual', 'no');
			update_post_meta( $post_id, '_regular_price', $produto['dados_gerais']['preco_normal'] );
			if ($produto['dados_gerais']['preco_oferta'] > 0) {
				update_post_meta( $post_id, '_sale_price', $produto['dados_gerais']['preco_oferta'] );
			}
			
			update_post_meta( $post_id, '_purchase_note', "" );
			update_post_meta( $post_id, '_featured', "no" );
			update_post_meta( $post_id, '_weight', $produto['dados_gerais']['peso'] );
			// update_post_meta( $post_id, '_length', $produto['dados_gerais']['profundidade'] );
			update_post_meta( $post_id, '_width', $produto['dados_gerais']['largura'] );
			update_post_meta( $post_id, '_height', $produto['dados_gerais']['altura'] );
			update_post_meta($post_id, '_sku', $produto['dados_gerais']['sku']);
			update_post_meta( $post_id, '_product_attributes', array());
			update_post_meta( $post_id, '_sale_price_dates_from', "" );
			update_post_meta( $post_id, '_sale_price_dates_to', "" );
			update_post_meta( $post_id, '_price', $produto['dados_gerais']['preco_normal']);
			update_post_meta( $post_id, '_sold_individually', "" );
			update_post_meta( $post_id, '_manage_stock', "yes" );
			update_post_meta( $post_id, '_backorders', "no" );
			update_post_meta( $post_id, '_stock', $produto['dados_gerais']['quantidade'] );					
			update_post_meta( $post_id, 'descricao_temporaria', utf8_decode(strip_tags($produto['dados_gerais']['descricao_completa'])));
			
			$msg_retorno = sprintf('Produto %s - %s %s com sucesso', $produto['dados_gerais']['id_original'], $produto['dados_gerais']['nome'], $novo_produto === false ? 'atualizado' : 'cadastrado');
			$arrRetorno[] = ['msg' => $msg_retorno, 'codigo_wordpress' => $product_id];
		}
		
		return $arrRetorno;
	}

	function pippin_get_image_id($image_url) {
		global $wpdb;
		$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url )); 
			return (int) $attachment[0]; 
	}
	
	function downloadImagem($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$imagem = curl_exec($ch);
		curl_close($ch);		
		return $imagem;
	}
	
	function validaJson($json){
	   return is_string($json) && is_array(json_decode($json, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
	}
	
	function atualizaCliente($arrDados) {
		foreach ($arrDados as $dado) {
			update_user_meta(
				$dado['codigo_cliente_wp'],
				'_codigo_cliente_tsd',
				$dado['codigo_cliente_tsd']
			);
		}
		return $this->retorno(1, 'Código do cliente atualizado com sucesso');
	}
	
	function atualizaPedido($arrDados) {
		foreach ($arrDados as $dado) {
			update_post_meta(
				$dado['codigo_pedido_wp'],
				'_numero_pedido_tsd',
				$dado['codigo_pedido_tsd']
			);
		}
		return $this->retorno(1, 'Código do pedido atualizado com sucesso');
	}
	
	function exportarPedidos($dt_filtro) {
		$arrData = explode('-', $dt_filtro);
		$args = array(
			'post_type' => wc_get_order_types(),
			'post_status' => array_keys( wc_get_order_statuses() ),
			'date_query' => array(
				array(
					'after'    => ['year' => $arrData[0], 'month' => $arrData[1], 'day' => $arrData[2]],
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

		return $arrPedidos;
	}
}
	
