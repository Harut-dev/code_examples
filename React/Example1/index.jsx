import React from 'react'
import {connect} from 'dva'
import {Form, Input, Button} from 'antd'
import {Link} from 'react-router-dom';

const mapStateToProps = (state, props) => ({})

@connect(mapStateToProps)
@Form.create()
export class LoginForm extends React.Component {
    static defaultProps = {};

    onSubmit = event => {
        event.preventDefault();
        const {form, isLoading, onSubmit} = this.props;
        if (!isLoading) {
            form.validateFields((error, values) => {
                if (!error) {
                    onSubmit(values)
                }
            })
        }
    };

    render() {
        const {form, isLoading, username, password} = this.props;

        return (
            <div className="cat__pages__login__block__form">
                <h4 className="text-uppercase">
                    <strong>Please log in</strong>
                </h4>
                <br/>
                <Form layout="vertical" hideRequiredMark onSubmit={this.onSubmit}>
                    <Form.Item label="Email">
                        {form.getFieldDecorator('username', {
                            initialValue: username,
                            rules: [
                                {required: true, message: 'Please input your e-mail address'}
                            ]
                        })(<Input size="default"/>)}
                    </Form.Item>
                    <Form.Item label="Password">
                        {form.getFieldDecorator('password', {
                            initialValue: password,
                            rules: [{required: true, message: 'Please input your password'}]
                        })(<Input size="default" type="password"/>)}
                    </Form.Item>
                    <div className="mb-2">
                        <Link
                            to={"/forgot"}
                            className="utils__link--blue utils__link--underlined"
                        >
                            Forgot password
                        </Link>
                    </div>
                    <div className="form-actions">
                        <Button
                            type="primary"
                            className="width-150 mr-4"
                            htmlType="submit"
                            loading={isLoading}
                        >
                            Login
                        </Button>
                    </div>
                </Form>
            </div>
        )
    }
}
