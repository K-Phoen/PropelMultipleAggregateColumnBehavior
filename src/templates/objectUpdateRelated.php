
/**
 * Update the aggregate columns in the <?php echo $relationName ?> object.
 *
 * @param PropelPDO $con A connection object
 */
protected function updateRelated<?php echo $relationName ?>(PropelPDO $con)
{
	if ($<?php echo $variableName ?> = $this->get<?php echo $relationName ?>()) {
		if (!$<?php echo $variableName ?>->isAlreadyInSave()) {
			<?php foreach ($updateMethods as $method): ?>
			$<?php echo $variableName ?>-><?php echo $method ?>($con);
			<?php endforeach; ?>
		}
	}
	if ($this->old<?php echo $relationName ?>) {
		<?php foreach ($updateMethods as $method): ?>
		$this->old<?php echo $relationName ?>-><?php echo $method ?>($con);
		<?php endforeach; ?>
		$this->old<?php echo $relationName ?> = null;
	}
}