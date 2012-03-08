<?php

function p() {
	print_r(func_get_arg(0));	
}

class Token {
	
	const TEXT = 0;
	const VARIABLE  = 1;
	const BLOCK = 2;
	const COMMENT = 3;
	
	public function __construct($type, $content) {
		
		$this->type    = $type;
		$this->content = $content;	
	}
	
	
}

class Lexer {
	
	public static $pattern,
	       $block_tag_len,
	       $var_tag_len,
	       $comment_tag_len;
	
	public $options = array(
		'block_start' => '{%',
		'block_end'   => '%}',
		'var_start' => '{{',
		'var_end'   => '}}',
		'comment_start' => '{*',
		'comment_end'   => '*}',
	);
	
	public $lineno = 1;
	
	public function __construct() {
		
		# cache pattern
		if (!self::$pattern) {
					
			$d = '@';
			
			self::$pattern = sprintf($d . '(%s.*?%s|%s.*?%s|%s.*?%s)' . $d,
			    preg_quote($this->options['block_start'], $d),
			    preg_quote($this->options['block_end'], $d),
			    preg_quote($this->options['var_start'], $d),
			    preg_quote($this->options['var_end'], $d),
			    preg_quote($this->options['comment_start'], $d),
			    preg_quote($this->options['comment_end'], $d)
			);
			
			self::$var_tag_len     = strlen($this->options['var_start']);
			self::$block_tag_len   = strlen($this->options['block_start']);
			self::$comment_tag_len = strlen($this->options['comment_start']);
		}
	}
	
	public function tokenize($content = null) {

		$in_tag = false;
		$result = array();
		$chunks = preg_split(self::$pattern, $content, null, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach($chunks as $c) {
			
			if ($c)
				$result[] = $this->create_token($c, $in_tag);
				
			$in_tag = !$in_tag;		
		}
		
		return $result;
	}

	/**
	 * Convert the given token string into a new Token object and return it.
     * If in_tag is True, we are processing something that matched a tag,
     * otherwise it should be treated as a literal string.
	 */	
	public function create_token($token_string, $in_tag) {
		
		$maps = array(
			'var_start' => array(
				'id'  => Token::VARIABLE,
				'len' => 2,
			),
		);
		
		if ($in_tag) {
			
			if ($pos = strpos($token_string, $this->options['var_start']) !== FALSE) {
				$token = new Token(Token::VARIABLE, trim(substr($token_string, 2, -2)));	
			}
			elseif ($pos = strpos($token_string, $this->options['block_start']) !== FALSE) {
				$token = new Token(Token::BLOCK, trim(substr($token_string, 2, -2)));	
			}
			elseif ($pos = strpos($token_string, $this->options['comment_start']) !== FALSE) {
				$token = new Token(Token::COMMENT, trim(substr($token_string, 2, -2)));	
			}
			# else ?
		}
		
		else {
			$token = new Token(Token::TEXT, $token_string);
		}
		
		$token->lineno  = $this->lineno;
		$this->lineno  += substr_count($token_string, "\n");
		
		return $token;
	}
}

class Parser {
	
	public $token_list = array(),
	       $node_list  = array();
	       
	public function __construct($tokens) {
		$this->token_list = $tokens;
	}
	
	public function parse() {
		
		$maps = array(
#			Token::VARIABLE => 
		);
		
		$node_list = array();
		while($token = array_shift($this->token_list)) {
			
			# TOKEN TEXT
			if ($token->type == 0)
				$node_list[] = new TextNode($token->content);
				
			# TOKEN VAR
			elseif ($token->type == 1) 
				$node_list[] = new VarNode($token->content);
				
			# TOKEN BLOCK
			elseif ($token->type == 2) 
				$node_list[] = new BlockNode($token->content);
		}
		
		# p($node_list);
		return $node_list;
	}
	
	public function next_token() {
		return current($this->token_list);
	}
}

class Node {
	
	public function render() {
		return $this->content;	
	}	
}

class TextNode extends Node {
	public function __construct($content) {
		$this->content = $content;	
	}
}

class VarNode extends Node {
	public function __construct($content) {
		$this->content = $content;	
	}	
}

class BlockNode extends Node {
	public function __construct($content) {
		$this->content = $content;	
	}
}

$l = new Lexer();
$tokens = $l->tokenize('{% if user %} <div>test</div> {{ user }} ?> {% else %} abc {% endif %}');

$p = new Parser($tokens);
$nodes  = $p->parse();

foreach($nodes as $n)
	echo $n->render();
	
echo PHP_EOL;