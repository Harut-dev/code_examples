<template>
	<div class="btn-icon" @click="$emit('click')">
		<slot name="icon"></slot>
	</div>
</template>
<script>
	export default {
		name: 'action-button',
		data () {
			return {

			}
		}
	}
</script>
