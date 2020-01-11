<?php

namespace Rookie0\Twig;

use Twig\Environment;
use Twig\Node\Expression\AssignNameExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\ForNode;
use Twig\Node\Node;

class Infer
{

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var array
     */
    protected $variables;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Infer template variables
     * @param string $name The template logical name
     * @param string $key
     * @return array|null
     */
    public function variables($name, $key = '')
    {
        if (! isset($this->variables[$name])) {
            $ast = $this->twig->parse(
                $this->twig->tokenize(
                    $this->twig->getLoader()->getSourceContext($name)));

            $this->variables[$name] = $this->infer($ast);
        }

        return $key ?
            $this->variables[$name][$key] ?? null :
            $this->variables[$name];
    }

    /**
     * @param Node  $ast
     * @param array $for [$seq, $val] loop vars
     * @return array
     */
    protected function infer(Node $ast, $for = [])
    {
        $variables = [];
        switch (get_class($ast)) {
            case NameExpression::class:
                if ($ast->hasAttribute('always_defined') &&
                    ! $ast->getAttribute('always_defined')) {
                    $variables = array_replace_recursive($variables, [
                        $ast->getAttribute('name') => [],
                    ]);
                }

                break;
            case GetAttrExpression::class:
                $variables = array_replace_recursive($variables, $this->visitGetAttrExpression($ast, '', $for));

                break;
            case ForNode::class:
                $variables = array_replace_recursive($variables, $this->visitForNode($ast));

                break;
            default:
                if ($ast->count()) {
                    foreach ($ast as $node) {
                        $variables = array_replace_recursive($variables, $this->infer($node, $for));
                    }
                }
        }

        return $variables;
    }

    /**
     * Visit Object|Array
     * @param Node   $ast
     * @param string $subKey sub object|array key
     * @param array  $for
     * @return array
     */
    protected function visitGetAttrExpression(Node $ast, $subKey = '', $for = [])
    {
        $node = $ast->getNode('node');
        // current node attribute
        $attr = $ast->getNode('attribute')->getAttribute('value');
        if (get_class($node) === NameExpression::class) {
            $key = $node->getAttribute('name');
            // special vars
            if (in_array($key, ['loop', '_self'])) {
                return [];
            }

            if ($subKey) {
                $subVar = [
                    $attr => [
                        $subKey => [],
                    ],
                ];
            } else {
                $subVar = [
                    $attr => [],
                ];
            }

            // for loop value
            if ($for && $for[0] && $for[1] === $key) {
                $var = [
                    $for[0] => [
                        $subVar,
                    ],
                ];
            } else {
                $var = [
                    $key => $subVar,
                ];
            }

            return $var;
        }

        return $this->visitGetAttrExpression($node, $attr, $for);
    }

    /**
     * Visit ForNode vars
     * @param Node $ast
     * @return array
     */
    protected function visitForNode(Node $ast)
    {
        $valNode  = $ast->hasNode('value_target') ? $ast->getNode('value_target') : null;
        $seqNode  = $ast->hasNode('seq') ? $ast->getNode('seq') : null;
        $bodyNode = $ast->hasNode('body') ? $ast->getNode('body') : null;

        $val = $valNode && get_class($valNode) === AssignNameExpression::class ? $valNode->getAttribute('name') : null;
        $seq = $seqNode && get_class($seqNode) === NameExpression::class ? $seqNode->getAttribute('name') : null;

        $vars = $this->infer($bodyNode, [$seq, $val]);

        return $seq ?
            array_replace_recursive($vars, [$seq => []]) :
            $vars;
    }

}