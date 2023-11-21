import terser from '@rollup/plugin-terser';

export default [
    {
        input: 'src/connect.js',
        output: [
            {
                file: 'dist/connect.es.js',
                format: 'es',
            },
            {
                file: 'dist/connect.js',
                format: 'iife',
            }
        ],
        plugins: []
    },
    {
        input: 'src/widget.js',
        output: [
            {
                file: 'dist/widget.es.js',
                format: 'es',
            },
            {
                file: 'dist/widget.js',
                format: 'iife',
            }
        ],
        plugins: []
    },
    {
        input: 'src/connect.js',
        output: [
            {
                file: 'dist/connect.min.js',
                format: 'iife',
            }
        ],
        plugins: [terser()]
    },
    {
        input: 'src/widget.js',
        output: [
            {
                file: 'dist/widget.min.js',
                format: 'iife',
            }
        ],
        plugins: [terser()]
    }
];

