import { Ability } from '@casl/ability';

// export default (user) => defineAbility((can) => {
//     can('read', 'Card');

//     if (user && (user.user === 'admin' || user === 'admin')) {
//         can('update', 'Card');
//         can('create', 'Card');
//     }
// });

export default (rules) => {
    return new Ability(rules)
}
