<template>
	<button
		:type="$props.type"
		ref="buttonRef"
		:class="['btn-' + $props.size, $props.classAttr, {'disabled-button': $props.disabledClass.isActive, 'hover-shadow-btn' : this.$props.hoverShadow} ]"
		@click="eventCall()"
		:style="getStyles"
		:disabled="$props.disabledClass.isActive"
		v-multi-event="{ evt: [...$props.emit], fn: emitMethods }"
	>
		<template v-if="$props.activeClass && $props.activeClass.icon && $props.activeClass.isActive">
			<template v-if="$props.activeClass.icon.dir === 'left'">
				<component v-if="$props.activeClass.icon.component"
						   :is="Icon[$props.activeClass.icon.component]"
						   :style="$props.icon.style"
				></component>
				<span v-else v-html="$props.activeClass.icon.element"></span>
			</template>
			<span class="text_wrap">{{$props.text}}</span>
			<template v-if="$props.activeClass.icon.dir === 'right'">
				<component v-if="$props.activeClass.icon.component" :is="Icon[$props.activeClass.icon.component]"></component>
				<span v-else v-html="$props.activeClass.icon.element"></span>
			</template>
		</template>
		<template v-else>
			<img v-if="$props.icon && $props.icon.file && $props.icon.dir === 'left'" :src="$props.icon.file" style="margin-right: 10px" />
			<component v-else-if="$props.icon.component && $props.icon.dir === 'left'"
					   :is="Icon[$props.icon.component]"
					   :style="$props.icon.style"></component>
			<span v-else-if="$props.icon && $props.icon.element && $props.icon.dir === 'left'" v-html="$props.icon.element"></span>
			<span v-if="$props.disabledClass.isActive" v-html="$props.disabledClass.loaderIcon"></span>
			<span class="text_wrap">{{$props.text}}</span>
			<img v-if="$props.icon && $props.icon.file && $props.icon.dir === 'right'" :src="$props.icon.file" style="margin-left: 10px" />
			<component v-else-if="$props.icon.component && $props.icon.dir === 'right'"
					   :is="Icon[$props.icon.component]"
					   :style="$props.icon.style"></component>
			<span v-else-if="$props.icon && $props.icon.element && $props.icon.dir === 'right'" v-html="$props.icon.element"></span>
		</template>

	</button>
</template>
<script>
	import Icon from 'components/pic-snippet/Icons';
	import Vue from "vue";
	export  default {
		name: 'Button-ex',
		components: {
		},
		props: {
			'emit': {
				required: false,
				default: arr => arr,
				type: Array
			},
			'type': {
				default: 'button',
				type: String,
				required: false
			},
			'radius': {
				default: 0,
				type: Number
			},
			'text': {
				default: '',
				type: String
			},
			'color': {
				default: '#0A273B',
				type: String
			},
			'bg': {
				default: 'transparent',
				type: String
			},
			'size': {
				default: 'md',
				type: String
			},
			'shadow': {
				default: '',
				type: String
			},
			'classAttr': {
				default: '',
				type: String
			},
			'border': {
				default: (message) => {
					return message
				},
				type: Object
			},
			'padding': {
				default: () => [7, 22],
				type: Array
			},
			'fontSize': {
				default: 18,
				type: Number
			},
			'margin': {
				default: () => {
					return [0,0,0,0]
				},
				type: Array
			},
			'fullWidth': {
				default: false,
				type: Boolean
			},
			'icon': {
				default: () => {
					return {}
				},
				type: Object
			},
			'activeClass': {
				default: () => {
					return {}
				},
				type: Object
			},
			'disabledClass': {
				default: () => {
					return {}
				},
				type: Object
			},
			'location': {
				type: String,
				required: false,
			},
			'direction': {
				default: '',
				type: String,
				required: false,
			},
			'hoverShadow': {
				default: '',
				type: String,
				required: false
			}
		},
		data () {
			return {
				Icon: Icon,
				hoverBoxShadow: ''
			}
		},
		computed: {
			getStyles () {
				return {
					display: this.$props.activeClass.isActive && this.$props.activeClass['display'] ||  (this.$props.fullWidth || this.$props.direction ? 'block' : 'inline-block'),
					padding: this.$props.padding[0] + 'px ' + this.$props.padding[1] + 'px',
					width: this.$props.activeClass.isActive && this.$props.activeClass['width'] || this.$props.fullWidth ? '100%' : 'initial',
					fontSize: this.$props.activeClass.isActive && this.$props.activeClass['fontsize'] || this.$props.fontSize + 'px',
					backgroundColor: (this.$props.activeClass.isActive && this.$props.activeClass['bg']) || this.$props.disabledClass['bg'] || this.$props.bg,
					borderRadius: this.$props.activeClass.isActive && this.$props.activeClass['radius'] || this.$props.radius + 'px',
					color: this.$props.activeClass.isActive && this.$props.activeClass['color'] || this.$props.color,
					boxShadow: (this.$props.activeClass.isActive && this.$props.activeClass['shadow']) || this.$props.disabledClass['shadow'] || this.$props.shadow,
					border: this.$props.activeClass.isActive && this.$props.activeClass['border'] || this.$props.border.size + 'px ' + 'solid ' + this.$props.border.color,
					margin: this.$props.activeClass.isActive && this.$props.activeClass['margin'] || this.$props.margin[0] + 'px ' + this.$props.margin[1] + 'px ' + this.$props.margin[2] + 'px ' + this.$props.margin[3] + 'px',
					...(this.$props.direction && {[this.$props.direction !== 'left' ? 'margin-left' : 'margin-right']: 'auto'}),
					...(this.hoverBoxShadow && {boxShadow: this.$props.hoverShadow}),
				}
			}
		},
		methods: {
			emitMethods (e) {
				// MouseEnter and MouseLeave event for Hover Shadow
				if (this.$props.hoverShadow && ((e.type === 'mouseenter') || (e.type === 'mouseleave'))) {
					this.hoverBoxShadow = (e.type === 'mouseenter') ? true : (e.type === 'mouseleave') ? false : '';
				}
			},
			eventCall (e) {
				if (this.$props.location) { // in case if button works like <a href=
					return window.location.href = this.$props.location;
				} else {
					this.$emit('onclick', e);
				}
			}
		}
	}


	function functionWrapper(e) {
		/* add filters to handle event type before propagating to callback function for custom event handler */
		e.target.__handler__.fn(e)
	}
	Vue.directive('multiEvent', {
		bind: function(el, binding, vnode) {
			el.__handler__ = binding.value;
			binding.value.evt.forEach(e => el.addEventListener(e, functionWrapper));
		},
		unbind: function(el, binding) {
			el.__handler__.evt.forEach(e => el.removeEventListener(e, functionWrapper));
			el.__handler__ = null
		}
	})
</script>
<style scoped>
	.hover-shadow-btn {
		transition: 0.4s;
	}
	.btn-md {
		white-space: nowrap;
		padding: 7px 20px;
		font-size: 18px;
		font-weight: bold;
		line-height: 20px;
		text-align: center;
		outline: none;
	}
	.disabled-button {
		pointer-events: none;
		opacity: 0.5;
	}
	button img {
		max-width: 20px;
	}
	.hover-btn {
		transition: .4s;
		transform: translateY(0px);
	}
	.hover-btn:hover {
		box-shadow: none !important;
		transform: translateY(2px);
	}
</style>
