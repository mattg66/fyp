import clsx from 'clsx';
import { Button } from 'flowbite-react';
import React, { memo, useState } from 'react';
import { Handle } from 'reactflow';

export default memo(({ data, id }: any) => {
    const [edit, setEdit] = useState(false)
    const submit = (e: React.FormEvent) => {
        e.preventDefault()
        setEdit(!edit)
    }
    return (
        <>
            <form className="group" onSubmit={submit}>
                {edit ? <input type="text" className="dark:text-black nodrag" value={data.label} onChange={(e) => data.onChange(e, id)} /> : <p>{data.label}</p>}
                <div className={clsx("group-hover:flex flex-wrap gap-2", !edit && 'hidden', edit && 'flex')}>
                    <Button className={clsx('nodrag', edit ? 'inline' : 'hidden')} type="submit">Save</Button>
                    <Button className={clsx('nodrag', edit ? 'hidden' : 'inline')} onClick={() => setEdit(!edit)}>Edit</Button>
                    <Button className='inline nodrag' color="failure">Delete</Button>
                </div>
            </form>
        </>
    );
});
