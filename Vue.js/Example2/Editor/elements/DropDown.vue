<template>
	<div class="absolute-parent">
		<div class="parent-container" :style="style">
			<slot name="main-content"></slot>
		</div>
	</div>
</template>
<script>
	export default {
		name: 'dropdown-ex',
		props: {
			popUpContainer: {
				type: Node,
				required: false,
				default: () => document.body
			},
			relativeRef: {
				type: String,
				required: true,
			},
			width: {
				type: Number,
				required: false,
				default: 200
			},
			offset: {
				type: Object,
				required: false,
				default: {x: 0, y: 0}
			},
			direction: {
				type: String,
				required: false,
				default: 'left'
			},
		},
		data () {
			return {
				style: {},
			}
		},
		mounted() {
			this.$props.popUpContainer.append(this.$el)
			this.$nextTick(() => {
				this.$props.popUpContainer.style.position = 'relative';
				if (this.$parent.$refs[this.$props.relativeRef]) {
					const popUpContainerPosition = this.$props.popUpContainer.getBoundingClientRect();
					const parentRef 	= this.$parent.$refs[this.$props.relativeRef];
					const _boundingPos  = (parentRef instanceof Array ? parentRef[0] : parentRef).getBoundingClientRect();
					const _elPos        = this.$el.querySelector('.parent-container').getBoundingClientRect();
					const	relativePos = {
						height: _boundingPos.height,
						width: _boundingPos.width,
						top: _boundingPos.top - popUpContainerPosition.top + this.$props.popUpContainer.scrollTop,
						right: _boundingPos.right - popUpContainerPosition.right,
						bottom: _boundingPos.bottom - popUpContainerPosition.bottom,
						left: _boundingPos.left - popUpContainerPosition.left
					}
					Object.assign(this.style, {
						width: this.$props.width + 'px',
						top: (relativePos.top + _boundingPos.height) + this.$props.offset.y + 'px',
						left: this.calcDirection(_elPos, relativePos) + this.$props.offset.x + 'px',
					})
					this.$forceUpdate();
				}
			})
		},
		methods: {
			calcDirection (elPos, posParent) {
				if (this.$props.direction === 'left') {
					return posParent.left
				}
				return posParent.left + posParent.width - elPos.width
			}
		},
	}
</script>
<style scoped>
	.absolute-parent {
		position: absolute;
		left: 0;
		top: 0;
		width: 100%;
		z-index: 222;
	}
 	.parent-container {
		position: absolute;
		z-index: 999;
		background-color: #fff;
		box-shadow: 0 4px 12px -6px rgba(10,39,59,.39), 0 0 6px 0 rgba(10,39,59,.25), 11px 15px 50px -6px rgba(10,39,59,.38), -2px 21px 50px -6px rgba(10,39,59,.2);
		border-radius: 9px;
		min-width: 120px;
	}
</style>
