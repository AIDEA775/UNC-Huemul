<?php
$path = realpath(dirname(__FILE__).'/../propiedades.ini');
$props = parse_ini_file($path, true);
$props = $props[$props['ambiente']];
  //$db = parse_ini_file('../../../instalacion/bases.ini', true);
$db = parse_ini_file($props['bases_ini_path'], true);
$sede = $_GET['sede'];

  class response {
    protected $responseHandler;
    protected $sql;
    protected $dbdata;
    
    function __construct() {
      $this->responseHandler = isset($_REQUEST['responseHandler'])?$_REQUEST['responseHandler']:'google.visualization.Query.setResponse';
    }

    function sql($sql) {
      $this->sql = $sql;
    }

    function dbdata($dbdata) {
      $this->dbdata = $dbdata;
    }

    function get_type($type, $data=false) {
      if(is_null($data)) return 'null';
      switch($type) {
		case "bool":
		  return ($data===false)?"'boolean'":($data=='t'?'true':'false');
		case "int2":
		case "int4":
		case "int8":
		case "float8":
		case "numeric":
		case "float4":
		  return ($data===false)?"'number'":$data;
		case "date":
		  if($data===false) {
		    return "'date'";
		  } else {
		    $ts = getdate(date_timestamp_get(date_create_from_format('Y-m-d',$data)));
		    return 'new Date('.$ts['year'].', '.($ts['mon']-1).', '.$ts['mday'].')';
		  }
		case "time":
		case "timetz":
		  if($data===false) {
		    return "'timeofday'";
		  } else {
		    $ts = getdate(date_timestamp_get(date_create_from_format('Y-m-d H:i:s',$data)));
		    return '['.$ts['hours'].', '.$ts['minutes'].', '.$ts['seconds'].']';
		  }
		case "timestamp":
		case "timestamptz":
		  if($data===false) {
		    return "'datetime'";
		  } else {
		    $ts = getdate(date_timestamp_get(date_create_from_format('Y-m-d H:i:s',$data)));
		    return 'new Date('.$ts['year'].', '.($ts['mon']-1).', '.$ts['mday'].', '.$ts['hours'].', '.$ts['minutes'].', '.$ts['seconds'].')';
		  }
		default:
		  return "'".(($data===false)?'string':$data)."'";
      }
    }

    function json() {
      $connStr = "host={$this->dbdata['profile']} port={$this->dbdata['puerto']} dbname={$this->dbdata['base']} user={$this->dbdata['usuario']} password={$this->dbdata['clave']}";
      $conn = pg_connect($connStr);
      if(!$conn) {
		$body = "'error', errors:[{reason: 'internal_error', message: 'No hay conexion con la base $connStr'}]";
      } else {
	$do_table=true;
	$result = pg_query($conn, $this->sql);
	switch(pg_result_status($result)) {
	    case PGSQL_FATAL_ERROR:
		$body = "'error', errors:[{reason: 'internal_error', message: '".pg_last_error($conn)."'}]";
		$do_table = false;
		break;
	    case PGSQL_NONFATAL_ERROR:
		$body = "'warning', warnings:[{reason: 'other', message: '".pg_last_error($conn)."'}]";
		break;
	    default:
		$body = "'ok'";
	}
	if($do_table) {
	  $cols = array();
	  $rows = array();
	  $body .= ', table: {cols:[';
	  for($i=0; $i<pg_num_fields($result); $i++) {
	    $cols[] = "{id: 'c$i', label: '".pg_field_name($result, $i)."', type:".$this->get_type(pg_field_type($result, $i))."}";
	  }
	  $body .= implode(', ', $cols).'], rows: [';
	  while($row = pg_fetch_row($result)) {
	    $tmp = '{c: [';
	    $c=array();
	    foreach($row as $i=>$col) {
	      $c[] = "{v: ".$this->get_type(pg_field_type($result, $i), $col)."}";
	    }
	    $rows[] = $tmp.implode(',',$c).']}';
	  }
	  $body .= implode(',',$rows).']}';
	}
      }
      $rid='';
      if(isset($_GET['tqx'])) { 
	$tqx = explode(';', $_GET['tqx']);
	foreach($tqx as $k=>$t) {
	  list($n, $v) = explode(':',$t);
	  $tqx[$n]=$v;
	  unset($tqx[$k]);
	  if($n=='reqId') {
	    $rid = $n.': '.$v.', ';
	  }
	}
      }
      return $this->responseHandler.'({'.$rid.'status:'.$body.'});';
    }
  }
  $r = new response();
  $r->dbdata($db['desarrollo comedor comedor']);
  
  switch($_REQUEST['accion']) {
    case 1: //Raciones consumidas

		$trunc = isset($_GET['trunc'])?$_GET['trunc']:'minute';
		//$r->sql("select (date_trunc('$trunc', fecha)::time)::varchar as fecha, count(*) as Raciones from fq_racion() where 
		//date_trunc('day', fecha) = date_trunc('day', current_timestamp) group by 1 order by 1");
		//$r->sql("select (date_trunc('$trunc', fecha)::time)::varchar as fecha, count(*) as Raciones from fq_racion_sede('$sede') where date_trunc('day', fecha) = date_trunc('day', current_timestamp) group by 1 order by 1");
		$r->sql("SELECT (date_trunc('$trunc', fecha)::time)::varchar as fecha, count(*) as Raciones FROM racion where sede = '$sede' and cast(fecha as date) = cast(current_timestamp as date) group by 1 order by 1");
                //select (date_trunc('$trunc', fecha)::time)::varchar as fecha, count(*) as Raciones from fq_racion_sede('$sede') where date_trunc('day', fecha) = date_trunc('day', current_timestamp) group by 1 order by 1");
		break;
    case 2:
		$g = 'day';
		$where = array();
		$filtro = explode(';', $_GET['filtro']);
		foreach($filtro as $f) {
		  list($k, $v) = explode(':', $f);
		  if(!is_null($v) && $v != '' && $v != 'nopar') {
		    switch($k) {
		      case 'tc':
			$where[] = 'tc.id_tipo_cliente = '.$v;
			break;
		      case 'fd':
			$where[] = 'date_trunc(\'day\',r.fecha) >= \''.$v.'\'';
			break;
		      case 'fh':
			$where[] = 'date_trunc(\'day\',r.fecha) <= \''.$v.'\'';
			break;
		      case 'tr':
			$where[] = 'r.tipo_racion = \''.$v.'\'';
			break;
     		      case 'sd':
			$where[] = 'r.sede =  \''.$v.'\'';
			break;
		      case 'g':
			$g = $v;
			break;
		    }
		  }
		}
		switch($_GET['agrupado']) {
		  case 'f':
		    if($g=='minute' && isset($_GET['mod'])) {
				$cols = "case 
				when r.fecha between date_trunc('hour', r.fecha) and date_trunc('hour', r.fecha)+interval '30 minutes' then 
					date_trunc('hour', r.fecha)+interval '30 minutes' 
				else 
					date_trunc('hour', r.fecha)+interval '1 hour' 
				end as Fecha";
		    } else {
		      	$cols = 'date_trunc(\''.$g.'\', r.fecha) as "Fecha"';
		    }
		    break;
		  case 'tr':
		    $cols = 'case when r.tipo_racion = \'R\' then \'Racion\' else \'Dinero\' end as "Tipo Racion"';
		    break;
		case 'sd':
		   $cols = 'r.sede as "Sede"';
 		    break;
		  default:
		    $cols = 'tc.descripcion as "Tipo Cliente"';
		    break;
		}
	
		$sql = "select $cols, 
				count(*) as \"Raciones\" 
			from 	fq_racion() r 
			inner join 
				fq_cliente() c 
			on 	r.id_cliente = c.id_cliente 
			inner join 
				fq_tipo_cliente() tc 
			on 	tc.id_tipo_cliente = c.id_tipo_cliente
			where	r.obsoleto = false
				and c.obsoleto = false
				and tc.obsoleto = false
			".(($where)?' and '.implode(' and ',$where):'').
			" group by 1
			order by 1";
		//echo $sql;
		$r->sql($sql);
		break;
    case 3:
		$where = array();
		$filtro = explode(';', $_GET['filtro']);
		foreach($filtro as $f) {
		  list($k, $v) = explode(':', $f);
		  if(!is_null($v) && $v != '' && $v != 'nopar') {
		    switch($k) {
		      case 'tc':
			$where[] = 'tc.id_tipo_cliente = '.$v;
			break;
		      case 'fd':
			$where[] = 'date_trunc(\'day\',v.fecha) >= \''.$v.'\'';
			break;
		      case 'fh':
			$where[] = 'date_trunc(\'day\',v.fecha) <= \''.$v.'\'';
			break;
		      case 'te':
			$where[] = 'e.id_extra = \''.$v.'\'';
			break;
		      case 'g':
			$g = $v;
			break;
		      case 'sd':
			$where[] = 'v.sede =  \''.$v.'\'';
                        break;
		    }
		  }
		}
	
		$operador = array('col'=>'', 'group'=>'');
		if($_GET['op']==1) {
			$operador['col'] = ', v.usuario as Operador';
			$operador['col2'] = ', Operador';
			$operador['group'] = ', 2';
		}
		switch($_GET['agrupado']) {
	//1/Ventas y Saldo, 2/Fecha, 3/Tipo Cliente, 4/Extras Vendidos, 5/Sedes
		  case 1:
		    if($_GET['sum']=='p') {
		      $sql = "select 	'Agrega Saldo' as \"Tipo Venta\"{$operador['col']}, 
					  sum(v.saldo)+sum(v.ingreso)-coalesce(sum(i.subtotal),0) as \"Pesos\"
		      from 	fq_venta() v
		      left join
			    fq_item_venta() i
		      on	i.id_venta = v.id_venta
			    and i.obsoleto = false
		      left join
			    fq_cliente() c
		      on	c.id_cliente = v.id_cliente
			    and c.obsoleto = false
		      left join
			    fq_tipo_cliente() tc
		      on	tc.id_tipo_cliente = c.id_tipo_cliente
			    and tc.obsoleto = false
		      left join
			    sedes s
		      on	s.sede = v.sede
		      left join
			    fq_extra() e
		      on	i.id_extra = e.id_extra
			    and e.obsoleto = false
		      where v.ingreso > 0
		      ".(($where)?' and '.implode(' and ',$where):'').
		      "group by 1{$operador['group']}
		      union
		      select 'Venta Extras'{$operador['col']}, 
			      sum(i.subtotal)-sum(v.saldo)
		      from 	fq_venta() v
		      inner join
			    fq_item_venta() i
		      on	i.id_venta = v.id_venta
			    and i.obsoleto = false
		      left join
			    fq_cliente() c
		      on	c.id_cliente = v.id_cliente
			    and c.obsoleto = false
		      left join
			    fq_tipo_cliente() tc
		      on	tc.id_tipo_cliente = c.id_tipo_cliente
			    and tc.obsoleto = false
		      left join
			    fq_extra() e
		      on	i.id_extra = e.id_extra
			    and e.obsoleto = false
		      where v.ingreso > 0
		      ".(($where)?' and '.implode(' and ',$where):'').
		      "group by 1{$operador['group']}
		      order by 1";
		    } else {
		      $sql = "select 	'Agrega Saldo' as \"Tipo Venta\"{$operador['col']}, 
				      count(*) as \"Cantidad\"
			      from 	fq_venta() v
			      left join
				    fq_item_venta() i
			      on	i.id_venta = v.id_venta
				    and i.obsoleto = false
			      left join
				    fq_cliente() c
			      on	c.id_cliente = v.id_cliente
				    and c.obsoleto = false
			      left join
				    fq_tipo_cliente() tc
			      on	tc.id_tipo_cliente = c.id_tipo_cliente
				    and tc.obsoleto = false
			      left join
				    fq_extra() e
			      on	i.id_extra = e.id_extra
				    and e.obsoleto = false
			      where 	v.ingreso > 0
			      ".(($where)?' and '.implode(' and ',$where):'').
			      "		and	i.id_venta is null
			      group by 1{$operador['group']}
			      union
			      select 	distinct 'Venta Extras'{$operador['col2']}, 
				      count(*)
			      from	(select distinct v.id_venta{$operador['col']}
				      from 	fq_venta() v
				      inner join
					    fq_item_venta() i
				      on	i.id_venta = v.id_venta
					    and i.obsoleto = false
				      left join
					    fq_cliente() c
				      on	c.id_cliente = v.id_cliente
					    and c.obsoleto = false
				      left join
					    fq_tipo_cliente() tc
				      on	tc.id_tipo_cliente = c.id_tipo_cliente
					    and tc.obsoleto = false
				      left join
					    fq_extra() e
				      on	i.id_extra = e.id_extra
					    and e.obsoleto = false
				      where 	v.ingreso > 0
				      ".(($where)?' and '.implode(' and ',$where):'').
				      " group by 1{$operador['group']}, v.ingreso
				      having 	sum(i.subtotal) >= v.ingreso
				      ) a
				  group by 1{$operador['group']}
			      union
			      select 	distinct 'Ambos'{$operador['col2']}, 
				      count(*)
			      from 	(select distinct v.id_venta{$operador['col']}
				      from 	fq_venta() v
				      inner join
					    fq_item_venta() i
				      on	i.id_venta = v.id_venta
					    and i.obsoleto = false
				      left join
					    fq_cliente() c
				      on	c.id_cliente = v.id_cliente
					    and c.obsoleto = false
				      left join
					    fq_tipo_cliente() tc
				      on	tc.id_tipo_cliente = c.id_tipo_cliente
					    and tc.obsoleto = false
				      left join
					    fq_extra() e
				      on	i.id_extra = e.id_extra
					    and e.obsoleto = false
				      where 	v.ingreso > 0
				      ".(($where)?' and '.implode(' and ',$where):'').
				      " group by 1{$operador['group']}, v.ingreso
				      having 	sum(i.subtotal) < v.ingreso
				      ) a
				  group by 1{$operador['group']}
			      order by 1";
		    }
		    break;
		  case 2:
		    if($g=='minute') {
			$cols = "case 
			when v.fecha between date_trunc('hour', v.fecha) and date_trunc('hour', v.fecha)+interval '30 minutes' then 
				date_trunc('hour', v.fecha)+interval '30 minutes' 
			else 
				date_trunc('hour', v.fecha)+interval '1 hour' 
			end as \"Fecha\"";
		    } else {
		      $cols = "date_trunc('$g', v.fecha) as \"Fecha\"";
		    }
		    break;
		  case 3:
		    $cols = "case when tc.id_tipo_cliente is null then 'Sin Cliente' else tc.descripcion end as \"Tipo Cliente\"";
		    break;
		  case 4:
		    $cols = "case when e.descripcion is null then 'Sin Extra' else e.descripcion end as \"Extra\"";
		  case 5:
		    $cols = "case when v.sede is null then 'Sede 47' else v.sede end as \"Sede\"";
		  break;
		}
		if(!isset($sql)) {
		  $sql = "select 	$cols{$operador['col']}, 
				    ".($_GET['sum']=='p'?'sum(v.ingreso) as "Pesos"':'count(*) as "Cantidad"')." 
			  from 	fq_venta() v
			  left join
				fq_item_venta() i
			  on	i.id_venta = v.id_venta
				and i.obsoleto = false
			  left join
				fq_cliente() c
			  on	c.id_cliente = v.id_cliente
				and c.obsoleto = false
			  left join
				fq_tipo_cliente() tc
			  on	tc.id_tipo_cliente = c.id_tipo_cliente
				and tc.obsoleto = false
			  left join
				fq_extra() e
			  on	i.id_extra = e.id_extra
				and e.obsoleto = false
			  where v.ingreso > 0
			  ".(($where)?' and '.implode(' and ',$where):'').
			  " group by 1{$operador['group']}
			    order by 1";
		}
		//echo "/*$sql*/";
		$r->sql($sql);
		break;
    case 4:
		if(isset($_POST['codigo'])) {
		  $cod = pg_escape_string($_POST['codigo']);
		  $sql = "select c.id_cliente,
				c.beca,
				c.renovado,
				coalesce(c.fecha_desde, e.fecha_desde) as fecha_desde,
				coalesce(c.fecha_hasta, e.fecha_hasta) as fecha_hasta,
				c.saldo,
				c.raciones,
  				c.sede,
				tc.descripcion as tipo_cliente,
				tc.tipo_beca,
				tc.tipo_monto,
				tc.beca as beca_tc,
				tc.tipo_duracion,
				tc.tipo_renovacion,
				tc.max_personas_agregar,
				tc.renovacion,
				p.nombre,
				p.apellido,
				p.cuip,
				p.otro,
				p.mail,
				e.nombre as evento,
				(select precio_x_racion from fq_parametros_generales() where obsoleto = false limit 1) as precio_x_racion,
				case when tc.tipo_duracion = 'R' then
				  case when tc.tipo_renovacion = 'F' then
				    case when renovacion ~ '^[0-9][0-9]?/[0-9][0-9]$' then
				      case when current_timestamp <= (renovacion||'/'||date_part('year', current_timestamp))::date then
					(renovacion||'/'||date_part('year', current_timestamp))::date
				      else
					(renovacion||'/'||date_part('year', (current_timestamp + interval '1 year')))::date
				      end
				    else
				      case when current_timestamp <= (renovacion||'/'||date_part('month', current_timestamp)||'/'||date_part('year', current_timestamp))::date then
					(renovacion||'/'||date_part('month', current_timestamp)||'/'||date_part('year', current_timestamp))::date
				      else
					(renovacion||'/'||date_part('month', (current_timestamp + interval '1 month'))||'/'||date_part('year', (current_timestamp + interval '1 month')))::date
				      end		    
				    end
				  else
				    (renovado + renovacion::interval)::date
				  end
				else 
				  null
				end as prox_renov,
				p.foto,
				c.codigo
			  from  ( select * from public.v_cliente_all WHERE codigo = '{$cod}') c
			  inner join
				fq_tipo_cliente() tc
			  on    tc.id_tipo_cliente = c.id_tipo_cliente
				and tc.obsoleto = false
			  left join
				fq_persona() p
			  on    c.id_persona = p.id_persona
				and p.obsoleto = false
			  left join
				fq_evento() e
			  on    e.id_evento = c.id_evento
				and e.obsoleto = false
			  ";
	
		} else {
		    $p = pg_escape_string($_POST['p']);
		    $lim = $_POST['m'] > 0?'limit '.pg_escape_string($_POST['m']):'';
		    $sql = "select p.nombre,
				  p.apellido,
				  p.cuip,
				  p.otro,
				  p.mail,
				  $p as id_cliente
			    from  fq_persona() p
			    inner join
				  fq_agregado() a
			    on	  p.id_persona = a.id_persona
			    where p.obsoleto = false
				  and a.obsoleto = false
				  and a.id_cliente = $p
			    order by a.orden
			    $lim";
		}
		$r->sql($sql);
		break;
    case 5:
    	$where = array();
    	$filtro = explode(';', $_GET['filtro']);
    	foreach($filtro as $f) {
    		list($k, $v) = explode(':', $f);
    		if(!is_null($v) && $v != '' && $v != 'nopar') {
    			switch($k) {
    				case 'tc':
    					$where[] = 'tc.id_tipo_cliente = '.$v;
    					break;
    				case 'fd':
    					$where[] = 'date_trunc(\'day\',v.fecha) >= \''.$v.'\'';
    					break;
    				case 'fh':
    					$where[] = 'date_trunc(\'day\',v.fecha) <= \''.$v.'\'';
    					break;
    				case 'te':
    					$where[] = 'e.id_extra = \''.$v.'\'';
    					break;
    				case 'g':
    					$g = $v;
    					break;
    			}
    		}
    	}
    	switch($_GET['agrupado']) {
    		case 'C':
		    	$sql = "select 	descripcion as \"Tipo Cliente\", 
					cant as \"Cantidad Clientes\",
					case when total is null or total = 0 then '0.0' else round(((cant::real/total::real)*100)::numeric, 1)::varchar end ||'%' as \"Porcentaje\"
				from
					(select tc.descripcion, 
					count(*) as cant
					from	fq_tipo_cliente() tc
					left join
							fq_cliente() c
					on		c.id_tipo_cliente = tc.id_tipo_cliente
							and c.obsoleto = false
					where	tc.obsoleto = false 
						 ".(($where)?' and '.implode(' and ',$where):'')."	
					group by 1) a,
					(select	count(*) as total
					from	fq_tipo_cliente() tc
					left join
							fq_cliente() c
					on		c.id_tipo_cliente = tc.id_tipo_cliente
							and c.obsoleto = false
					where	tc.obsoleto = false 
						 ".(($where)?' and '.implode(' and ',$where):'')."
					)b
		    	";
		    	break;
    		case 'R':
    			$sql = "select 	descripcion as \"Tipo Cliente\", 
					rac as \"Raciones\", 
					case when total is null or total = 0 then '0.0' else round(((rac::real/total::real)*100)::numeric, 1)::varchar end ||'%' as \"Porcentaje\"
				from
					(select 	tc.descripcion,
						sum(coalesce(raciones,0)) as rac
					from	fq_tipo_cliente() tc
					left join
							fq_cliente() c
					on		c.id_tipo_cliente = tc.id_tipo_cliente
							and c.obsoleto = false
					where	tc.obsoleto = false
					".(($where)?' and '.implode(' and ',$where):'')."
					group by 1) a,
					(select sum(coalesce(raciones,0)) as total
					from	fq_tipo_cliente() tc
					left join
							fq_cliente() c
					on		c.id_tipo_cliente = tc.id_tipo_cliente
							and c.obsoleto = false
					where	tc.obsoleto = false
					".(($where)?' and '.implode(' and ',$where):'')."
					) b
    			";
    			break;
    		case 'S':
    			$sql = "select 	descripcion as \"Tipo Cliente\",
					saldo as \"Saldo\",
					case when total is null or total = 0 then '0.0' else round(((saldo::real/total::real)*100)::numeric, 1)::varchar end ||'%' as \"Porcentaje\"
				from 
					(select 	tc.descripcion,
						sum(coalesce(saldo,0)) as saldo
					from	fq_tipo_cliente() tc
					left join
							fq_cliente() c
					on		c.id_tipo_cliente = tc.id_tipo_cliente
							and c.obsoleto = false
					where	tc.obsoleto = false 
						 ".(($where)?' and '.implode(' and ',$where):'')."	
					group by 1) a,
					(select sum(coalesce(saldo,0)) as total
					from	fq_tipo_cliente() tc
					left join
							fq_cliente() c
					on		c.id_tipo_cliente = tc.id_tipo_cliente
							and c.obsoleto = false
					where	tc.obsoleto = false 
						 ".(($where)?' and '.implode(' and ',$where):'')."	
					) b
				";
    			break;
    	}
    	$r->sql($sql);
    	break;
  }
  echo $r->json();
?>
