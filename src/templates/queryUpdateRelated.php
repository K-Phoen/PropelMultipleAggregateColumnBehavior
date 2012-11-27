
protected function updateRelated<?php echo $relationName ?>s($con)
{
	foreach ($this-><?php echo $variableName ?>s as $<?php echo $variableName ?>) {
		<?php foreach ($updateMethodNames as $method): ?>
		$<?php echo $variableName ?>-><?php echo $method ?>($con);
		<?php endforeach; ?>
	}
	$this-><?php echo $variableName ?>s = array();
}
