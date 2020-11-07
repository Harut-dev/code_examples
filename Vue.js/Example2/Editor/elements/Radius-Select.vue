<template>
	<div class="main-select" :style="{margin: $props.margin[0] + 'px ' + $props.margin[1] + 'px ' + $props.margin[2] + 'px ' + $props.margin[3] + 'px'}"  :class="isOpen ? 'open-dropdown' : 'hide-dropdown'">
		<div class="select-header">
			<slot name="select-header"></slot>
		</div>
		<div class="select-body" :style="{width: $props.widthBody + 'px'}">
			<slot name="select-body" :searchValue="searchValue" :isOpen="isOpen" :onchange="onChangeValue"></slot>
		</div>
	</div>
</template>
<script>
	import _mixins from '../../../helpers/mixins';
	export default {
		mixins: [_mixins],
		name: 'radius-select',
		props: {
			'widthBody': {
				default: 100,
				type: Number,
				required: false
			},
			'margin': {
				default: () => [0, 0, 0, 0],
				type: Array,
				required: false
			}
		},
		data () {
			return {
				searchValue: '',
				isOpen: false
			}
		},
		methods: {
			onChangeValue (evt) {
				let _evt = evt.target || evt.currentTarget;
				this.searchValue = _evt.value;
			},
			removeValue (val) {
				this.searchValue = val
			},
			openDropdown () {
				this.isOpen = !this.isOpen;
				this.addRemoveEventOnDocument('closeModalSelf');
			},
			closeModalSelf (e) {
				const target = e.target || e.currentTarget;
				this.closeModal(!target.closest('.select-body'), () => {
					this.isOpen = false;
				})
			}
		}
	}
</script>
