import { Component } from "@wordpress/element";
import { TextControl } from "@wordpress/components";

export default class LazyTextControl extends Component {
    constructor( props ) {
        super();

        this.state = {
            value: props.value
        };

        this.handleChange = this.handleChange.bind( this );
        this.triggerChange = this.triggerChange.bind( this );
    }

    componentDidMount() {
        this.timer = null;
    }

    handleChange( value ) {
        clearTimeout( this.timer );
        this.setState({ value } );
        this.timer = setTimeout( this.triggerChange, this.props.timeout );
    }

    triggerChange() {
        const { value } = this.state;

        this.props.onChange( value );
    }

    render() {
        return (
            <TextControl
                { ...this.props }
                value={ this.state.value }
                onChange={ this.handleChange }
            />
        );
    }
}