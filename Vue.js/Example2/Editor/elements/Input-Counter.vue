<template>
		<div class="snippets-header_counter" :class="[$props.className, ($props.disabled || !value && value !== 0) ? 'disable_counter' : '']">
		<div class="main-wrap-counter">
			<div ref="likeInput" :style="{width: maxWidthInput + 'px'}" :class="{'enter-editing': enterEditing}">
				<template v-if="value || value === 0">
					<component :is="dynamicComponent" v-if="$props.icon" style="margin-right: 5px; margin-top: -3px;"></component>
					<span
						:ref="uniqId"
						@dblclick="dblClickCounter"
						@blur="deActivateEditing"
						@focus="activateEditing"
						@keypress="setCounterValue"
						:contenteditable="dblClickEdited"
						:set="changeEditableText(counterValue)"
					></span>
				</template>
			</div>
			<div class="upper_down">
				<span @click="onPlus()">
					<plus />
				</span>
				<span @click="onMinus()">
					<minus />
				</span>
			</div>
		</div>
	</div>
</template>
<script>
	import Icons from 'components/pic-snippet/Icons';

	export  default {
		components: {
			'plus': Icons.plus,
			'minus': Icons.minus,
		},
		props: {
			'value': {
				default: null,
				type: Number,
				required: false
			},
			'by': {
				default: '',
				type: String,
				required: false
			},
			'everyStep': {
				default: 1,
				type: Number,
				required: true
			},
			'maxValue': {
				default: null,
				type: Number,
				required: false
			},
			'minValue': {
				default: 0,
				type: Number,
				required: false
			},
			'class-name': {
				default: '',
				type: String,
				required: false
			},
			'stateProp': {
				default: '',
				type: String,
				required: false
			},
			'disabled': {
				default: false,
				type: Boolean,
				required: false
			},
			'icon': {
				default: '',
				type: String,
				required: false
			}
		},
		name: "pic-snippets-header",
		data () {
			return {
				uniqId: 'input-counter-' + Math.random().toString(16).slice(2, 6), // Every Input Counter should have himself uniq ID for ref
				enterEditing: false,
				dblClickEdited: false,
				inputValue: 0,
				newVal: 0,
				editableText: '',
				...(this.$props.icon && {dynamicComponent: Icons[this.$props.icon]})
			}
		},
		computed: {
			counterValue () {
				this.editableText = '';
				return this.value  + this.$props.by
			},
			maxWidthInput () {
				const _widthEveryChar = 10;
				return ((this.$props.maxValue + this.$props.by).toString().length * _widthEveryChar) + 20/*paddings right and left*/;
			}
		},
		mounted() {
			this.$refs[this.uniqId].innerText = this.counterValue
		},
		methods: {
			changeEditableText (val) {
				if(this.$refs[this.uniqId]) {
					this.$refs[this.uniqId].innerText = this.editableText ||  this.counterValue
				}
			},
			setCounterValue (evt) {
				const e = evt || window.event; //window.event is safer
				const charCode = e.which || e.keyCode;
				if (charCode === 13) {
					this.$refs[this.uniqId].blur();
					evt.preventDefault();
				}
				if (!(charCode > 31 && (charCode < 46 || charCode > 57))){
					return true
				} else if (!this.$props.by.includes(evt.key)) {
					evt.preventDefault();
				}
				return true;
			},
			dblClickCounter () {
				this.dblClickEdited = true;
				this.$nextTick(() => {
					this.$refs[this.uniqId].focus();
				})
			},
			deActivateEditing () {
				const indexDot = this.$refs[this.uniqId].innerText.indexOf('.');
				let numbValue = this.$refs[this.uniqId].innerText.match(/\d/g);
				numbValue = numbValue.join('');
				this.enterEditing = false;
				this.dblClickEdited = false;
				if (indexDot > 0) {
					numbValue =  numbValue.slice(0, indexDot) + '.'	+ numbValue.slice(indexDot);
				}
				if(+numbValue > this.maxValue) numbValue = this.maxValue;
				this.editableText = numbValue + this.$props.by;
				if (+numbValue < this.minValue) {
					this.editableText = this.minValue + this.$props.by;
					this.$emit('change', {prop: this.stateProp, val: this.minValue, type: (+numbValue < this.value) ? 'minus' : 'plus'});
				} else if (+numbValue !== this.value) {
					this.$emit('change', {prop: this.stateProp, val: +numbValue, type: (+numbValue < this.value) ? 'minus' : 'plus'});
				}
			},
			activateEditing () {
				this.enterEditing = true;
			},

			onPlus() {
				let value = this.value + this.everyStep;
				if(value > this.maxValue) value = this.maxValue;
				this.editableText = '';
				this.$emit('change', {prop: this.stateProp, val: value, type: 'plus'});
			},

			onMinus() {
				let value = this.value - this.everyStep;
				if((this.value - this.everyStep) < this.minValue) value = this.minValue;
				this.editableText = '';
				this.$emit('change',{prop: this.stateProp, val: value, type: 'minus'});
			},
		}
	}
</script>
